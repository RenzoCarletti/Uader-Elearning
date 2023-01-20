<?php

namespace Drupal\moodle_rest_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\migrate\Plugin\migrate\process\FileProcessBase;
use Drupal\moodle_rest\Services\MoodleRest;

/**
 * Imports a file from Moodle.
 *
 * Required configuration keys:
 * - source: The source path or URI, e.g. '/path/to/foo.txt' or
 *   'public://bar.txt'.
 *
 * Optional configuration keys:
 * - destination_dir: The destination path or URI to import the file to.
 *   If no destination is set, it will default to "public://".
 * - destination_name: Destination filename. Default will be source filename.
 * - uid: The uid to attribute the file entity to. Defaults to 0
 * - rename: 0, 1, 2 as defined by FileSystemInterface::EXISTS_RENAME (0),
 *   FileSystemInterface::EXISTS_REPLACE (1), FileSystemInterface::EXISTS_ERROR
 *   (2). Default FileSystemInterface::EXISTS_REPLACE.
 * - reuse: Boolean, if TRUE, reuse the current file in its existing
 *   location rather than move/copy/rename the file. Defaults to FALSE.
 * - id_only: Boolean, if TRUE, the process will return just the id instead of
 *   an entity reference array. Useful if you want to manage other sub-fields
 *   in your migration (see example below).
 * - skip_on_error: Send a migrate skip processing on error if file fails to be
 *   downloaded or saved.
 *
 * Example:
 *
 * @code
 * process:
 *   field_course_image:
 *     plugin: moodle_file
 *     source: overviewfiles/0/fileurl
 *     destination_dir: 'public://moodle_files'
 *     uid: @uid
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "moodle_file"
 * )
 */
class MoodleFile extends FileProcessBase implements ContainerFactoryPluginInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Moodle rest connection.
   *
   * @var \Drupal\moodle_rest\Services\MoodleRest
   */
  protected $moodle;

  /**
   * Constructs a file_copy process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrappers
   *   The stream wrapper manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\moodle_rest\Services\MoodleRest $moodle
   *   The moodle rest connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, MoodleRest $moodle) {
    $configuration += [
      'destination_dir' => 'public://',
      'destination_name' => NULL,
      'uid' => 0,
      'rename' => FileSystemInterface::EXISTS_RENAME,
      'reuse' => TRUE,
      'id_only' => FALSE,
      'skip_on_error' => FALSE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->streamWrapperManager = $stream_wrappers;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->moodle = $moodle;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('moodle_rest.rest_ws')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!$value) {
      return NULL;
    }

    // Get our file entity values.
    $source = $value;
    $destination_dir = $this->getPropertyValue($this->configuration['destination_dir'], $row);
    $uid = $this->getPropertyValue($this->configuration['uid'], $row);
    $id_only = $this->configuration['id_only'];
    $destination_file = $this->getPropertyValue($this->configuration['destination_name'], $row) ?: $this->getDestinationFilename($value, $destination_dir);
    $rename = $this->getPropertyValue($this->configuration['rename'], $row);

    // If we're in re-use mode, reuse the file if it exists.
    if ($this->getPropertyValue($this->configuration['reuse'], $row)&& $this->isLocalUri($destination_file) && is_file($destination_file)) {
      // Look for a file entity with the destination uri.
      if ($files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $destination_file])) {
        // Grab the first file entity with a matching uri.
        // @todo: Any logic for preference when there are multiple?
        $file = reset($files);
        // Set to permanent if the file in the database is set to temporary.
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }

        return $id_only ? $file->id() : ['target_id' => $file->id()];
      }
      else {
        $final_destination = $destination_file;
      }
    }
    else {
      try {
        // Check dir. But FileBlob and another? try and move file first to
        // avoid doing this everytime?
        $this->fileSystem->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        // Write file.
        $source_stream = $this->moodle->requestFile($source);
        $final_destination = $this->fileSystem->saveData($source_stream->getBody(), $destination_file, $rename);
      }
      catch (\Exception $e) {
        // Check if we're skipping on error
        if ($this->configuration['skip_on_error']) {
          $migrate_executable->saveMessage("File $source could not be imported to $destination_file. Operation failed with message: " . $e->getMessage());
          throw new MigrateSkipProcessException($e->getMessage());
        }
        else {
          // Pass the error back on again.
          throw new MigrateException($e->getMessage());
        }
      }
    }

    if ($final_destination) {
      // Create a file entity.
      $file = File::create([
        'uri' => $final_destination,
        'uid' => $uid,
        'status' => \FILE_STATUS_PERMANENT,
      ]);
      $file->save();
      return $id_only ? $file->id() : ['target_id' => $file->id()];
    }

    throw new MigrateException("File $source could not be imported to $destination_file");
  }

  /**
   * Build destination filename.
   */
  private function getDestinationFilename($source, $destination_dir) {
    $source_parts = parse_url($source);
    $filename = $this->fileSystem->basename($source_parts['path']);
    return $destination_dir . '/' . $filename;
  }

  /**
   * Gets a value from a source or destination property.
   *
   * Code is adapted from Drupal\migrate\Plugin\migrate\process\Get::transform()
   */
  protected function getPropertyValue($property, $row) {
    if (is_string($property)) {
      $is_source = TRUE;
      if (substr($property, 0, 1) == '@') {
        $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', function ($matches) use (&$is_source) {
          // If there are an odd number of @ in the beginning, it's a
          // destination.
          $is_source = empty($matches[1]);
          // Remove the possible escaping and do not lose the terminating
          // non-@ either.
          return str_replace('@@', '@', $matches[2]) . $matches[3];
        }, $property);
        if ($is_source) {
          return $row->getSourceProperty($property);
        }
        else {
          return $row->getDestinationProperty($property);
        }
      }
    }
    return $property;
  }

  /**
   * Determines if the given URI or path is considered local.
   *
   * A URI or path is considered local if it either has no scheme component,
   * or the scheme is implemented by a stream wrapper which extends
   * \Drupal\Core\StreamWrapper\LocalStream.
   *
   * @param string $uri
   *   The URI or path to test.
   *
   * @return bool
   *   True if local.
   */
  protected function isLocalUri($uri) {
    $scheme = StreamWrapperManager::getScheme($uri);

    // The vfs scheme is vfsStream, which is used in testing. vfsStream is a
    // simulated file system that exists only in memory, but should be treated
    // as a local resource.
    if ($scheme == 'vfs') {
      $scheme = FALSE;
    }
    return $scheme === FALSE || $this->streamWrapperManager->getViaScheme($scheme) instanceof LocalStream;
  }

}
