<?php

namespace Drupal\moodle_rest_migrate\Plugin\migrate\source;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\moodle_rest\Services\MoodleRest;
use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use PHPUnit\TextUI\XmlConfiguration\MigrationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for retrieving data from Moodle REST.
 *
 * @MigrateSource(
 *   id = "moodle_base",
 *   source_module = "moodle_rest_migrate"
 * )
 */
class MoodleBase extends SourcePluginBase implements ConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MoodleRest $rest_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->setConfiguration($configuration);
    // Function is required.
    if (empty($this->configuration['function'])) {
      throw new \InvalidArgumentException('You must declare the "function" for the Moodle Rest service.');
    }
    $this->restClient = $rest_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('moodle_rest.rest_ws')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'function' => '',
      'arguments' => [],
      'ids' => ['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = array_merge($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->configuration['function'];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];
    if ($results = $this->getResults()) {
      $fields = array_combine(array_keys($results[0]), array_keys($results[0]));
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];
    foreach ($this->configuration['ids'] as $id_label) {
      $ids[$id_label]['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function initializeIterator() {
    foreach ($this->getResults() as $result) {
      yield $result;
    }
  }

  /**
   * Retrieve results.
   */
  public function getResults() {
    try {
      $result = $this->restClient->requestFunction($this->configuration['function'], $this->configuration['arguments']);
    }
    catch (MoodleRestException $e) {
      throw new MigrationException('Moodle REST Webservice Exception', $e->getCode(), $e);
    }

    return $result;
  }

}
