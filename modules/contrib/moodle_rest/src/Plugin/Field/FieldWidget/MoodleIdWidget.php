<?php

namespace Drupal\moodle_rest\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'number' widget.
 *
 * @FieldWidget(
 *   id = "moodle_id",
 *   label = @Translation("ID number"),
 *   field_types = {
 *     "moodle_id"
 *   }
 * )
 */
class MoodleIdWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;

    $element += [
      '#type' => 'number',
      '#default_value' => $value,
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

}
