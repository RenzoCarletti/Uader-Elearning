<?php

namespace Drupal\eca_form\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca\Plugin\FormFieldPluginTrait;

/**
 * Compares a form field value.
 *
 * @EcaCondition(
 *   id = "eca_form_field_value",
 *   label = "Form field: compare submitted value"
 * )
 */
class FormFieldValue extends StringComparisonBase {

  use FormFieldPluginTrait;

  /**
   * {@inheritdoc}
   *
   * The left value must not be replaced with Tokens, as this may be arbitrary
   * user input, including from untrusted users.
   */
  protected static bool $replaceTokens = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function getLeftValue(): string {
    if (!$this->getCurrentFormState()) {
      // Since the StringComparisonBase always compares string values, we want
      // to make sure, that the evaluation will return FALSE when there is no
      // current form state available.
      return '_FORM_STATE_IS_MISSING_';
    }
    $value = $this->getSubmittedValue();
    if (is_array($value)) {
      $first_val = NULL;
      array_walk_recursive($value, function ($v) use (&$first_val) {
        if (!isset($first_val) && is_scalar($v) && trim((string) $v) !== '') {
          $first_val = $v;
        }
      });
      $value = $first_val;
    }
    if (is_scalar($value) || is_null($value)) {
      $value = trim((string) $value);
    }
    else {
      return '_VALUE_NOT_RESOLVABLE_TO_STRING_';
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightValue(): string {
    return $this->tokenServices->replaceClear($this->configuration['field_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_value' => '',
    ] + $this->defaultFormFieldConfiguration() + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['field_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field value'),
      '#description' => $this->t('This field supports tokens.'),
      '#default_value' => $this->configuration['field_value'],
      '#weight' => -70,
    ];
    return $this->buildFormFieldConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->validateFormFieldConfigurationForm($form, $form_state);
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_value'] = $form_state->getValue('field_value');
    $this->submitFormFieldConfigurationForm($form, $form_state);
    parent::submitConfigurationForm($form, $form_state);
  }

}
