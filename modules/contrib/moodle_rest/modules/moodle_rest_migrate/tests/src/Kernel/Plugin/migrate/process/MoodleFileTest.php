<?php

namespace Drupal\Tests\moodle_rest_migrate\Kernel\Plugin\migrate\process;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\KernelTests\Core\File\FileTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\moodle_rest\Services\MoodleRest;
use Drupal\moodle_rest_migrate\Plugin\migrate\process\MoodleFile;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\moodle_rest_migrate\Plugin\migrate\process\MoodleFile
 *
 * @group moodle_rest_migrate
 */
class MoodleFileTest extends FileTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'system',
    'user',
    'file',
    'moodle_rest_migrate',
  ];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->fileSystem = $this->container->get('file_system');
    $this->container->get('stream_wrapper_manager')->registerWrapper('temporary', 'Drupal\Core\StreamWrapper\TemporaryStream', StreamWrapperInterface::LOCAL_NORMAL);
  }

  /**
   * Test basic functionality with defaults.
   */
  public function testRetrieveSave() {
    $source = '/path/file';
    $this->doTransform($source, '', []);
  }

  /**
   * Do an import using the destination.
   *
   * @param string $moodle_uri
   *   Source URL to copy from.
   * @param string $destination_dir
   *   The destination directory to copy to.
   * @param array $configuration
   *   Process plugin configuration settings.
   *
   * @return string
   *   The URI of the copied file.
   */
  protected function doTransform($moodle_uri, $destination_path, $configuration = []) {
    // Prepare a mock HTTP client.
    $moodle = $this->createMock(MoodleRest::class);
    $moodle->method('requestFile')->willReturn(new Response(200, [], 'abc'));
    $this->container->set('moodle_rest.rest_ws', $moodle);

    $plugin = MoodleFile::create($this->container, $configuration, 'moodle_file', []);
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row([], []);

    return $plugin->transform($moodle_uri, $executable, $row, 'foobaz');
  }

}
