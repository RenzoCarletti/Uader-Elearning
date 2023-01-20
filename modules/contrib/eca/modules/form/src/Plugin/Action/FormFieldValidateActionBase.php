<?php

namespace Drupal\eca_form\Plugin\Action;

/**
 * Base class for form field validation actions.
 */
abstract class FormFieldValidateActionBase extends FormFieldActionBase {

  /**
   * Whether to use form field value filters or not.
   *
   * @var bool
   */
  protected bool $useFilters = FALSE;

  /**
   * Set a form error to the configured field.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $message
   *   The error message.
   */
  protected function setError($message): void {
    if (!($form_state = $this->getCurrentFormState())) {
      return;
    }
    // Convert the field name to the bracket syntax as required by
    // FormStateInterface::setErrorByName().
    $name = str_replace(']', '', $this->configuration['field_name']);
    $name = str_replace('[', '][', $name);

    $form_state->setErrorByName($name, $message);
  }

}
