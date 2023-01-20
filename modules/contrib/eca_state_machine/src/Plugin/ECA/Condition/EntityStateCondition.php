<?php

namespace Drupal\eca_state_machine\Plugin\ECA\Condition;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\state_machine\Plugin\Field\FieldType\StateItem;

/**
 * Plugin of the ECA condition for current entity state from State Machine.
 *
 * @EcaCondition(
 *   id = "eca_state_machine_entity_state",
 *   label = @Translation("State Machine: Entity State"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityStateCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getValueFromContext('entity');
    if ($entity instanceof FieldableEntityInterface) {
      $field_name = $this->tokenServices->replaceClear($this->configuration['field_name']);
      $parts = explode('-', $this->configuration['state_id']);
      if (!empty($field_name) && count($parts) === 2 && $entity->hasField($field_name)) {
        $field = $entity->get($field_name);
        if (!($field instanceof StateItem)) {
          return FALSE;
        }
        $result = ($field->getValue()[0]['value'] === $parts[1]);
        return $this->negationCheck($result);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
      'state_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = [];
    /** @var \Drupal\state_machine\WorkflowManager $manager */
    $manager = \Drupal::service('plugin.manager.workflow');
    foreach ($manager->getDefinitions() as $wid => $definition) {
      try {
        $workflow = $manager->createInstance($wid);
      }
      catch (PluginException $e) {
        continue;
      }
      foreach ($workflow->getStates() as $state_id => $state) {
        $options[$wid . '-' . $state_id] = $workflow->getLabel() . ': ' . $state->getLabel();
      }
    }
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    $form['state_id'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $options,
      '#default_value' => $this->configuration['state_id'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    $this->configuration['state_id'] = $form_state->getValue('state_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
