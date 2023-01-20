<?php

namespace Drupal\moodle_rest\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Moodle ID' formatter.
 *
 * @FieldFormatter(
 *   id = "moodle_id",
 *   label = @Translation("ID number"),
 *   field_types = {
 *     "moodle_id"
 *   }
 * )
 */
class MoodleIdFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getFieldSettings();

    foreach ($items as $delta => $item) {
      $output = (int) $item->value;
      $elements[$delta] = ['#markup' => $output];
    }

    return $elements;
  }

}
