<?php

namespace Drupal\moodle_rest_migrate\Plugin\migrate\source;

use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;

/**
 * Source plugin for retrieving courses by field from Moodle REST.
 *
 * @MigrateSource(
 *   id = "moodle_get_courses_by_field",
 *   source_module = "moodle_rest_migrate"
 * )
 *
 * @code
 *   source:
 *     plugin: moodle_get_courses_by_field
 *     arguments:
 *       field: 'category'
 *       value: 1
 * @endcode
 *
 * @see RestFunctions::getCoursesByField for more about argument fields.
 */
class MoodleGetCoursesByField extends MoodleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'function' => 'core_course_get_courses_by_field',
      'arguments' => [],
      'ids' => ['id'],
    ];
  }

  /**
   * Retrieve results.
   */
  public function getResults(): array {
    try {
      $result = $this->restClient->requestFunction($this->configuration['function'], $this->configuration['arguments']);
    }
    catch (MoodleRestException $e) {
      throw new MigrateException(
        'Moodle REST: ' . $e->getMessage() . ' - '
          . $e->getBody()['message'],
        $e->getCode(),
        $e
      );
    }

    // If we can associate the warnings with a row in the idmap do that.
    // Otherwise set a normal message.
    if (!empty($result['warnings'])) {
      foreach ($result['warnings'] as $warning) {
        if ($this->configuration['ids'] == ['id'] && !empty($warning['itemid'])) {
          $this->idMap->saveMessage(['id' => $warning['itemid']], $warning['message'], MigrationInterface::MESSAGE_WARNING);
        }
        else {
          $this->messenger->addWarning('Moodle webservice warning: ' . print_r($warning, TRUE));
        }
      }
    }

    return $result['courses'];
  }

}
