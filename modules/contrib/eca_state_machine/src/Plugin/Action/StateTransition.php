<?php

namespace Drupal\eca_state_machine\Plugin\Action;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\state_machine\Plugin\Field\FieldType\StateItem;

/**
 * Trigger a content entity state transition with State Machine.
 *
 * @Action(
 *   id = "eca_state_machine_transition",
 *   label = @Translation("State Machine: trigger entity state transition"),
 *   type = "entity"
 * )
 */
class StateTransition extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \InvalidArgumentException
   */
  public function execute($entity = NULL): void {
    if (!($entity instanceof FieldableEntityInterface)) {
      throw new \InvalidArgumentException('No fieldable content entity provided.');
    }
    $field_name = $this->tokenServices->replaceClear($this->configuration['field_name']);
    $parts = explode('-', $this->configuration['transition_id']);
    if (empty($field_name) || count($parts) < 3 || !$entity->hasField($field_name)) {
      throw new \InvalidArgumentException('Entity does not have the requested field.');
    }
    $field = $entity->get($field_name);
    if (!($field instanceof StateItem)) {
      throw new \InvalidArgumentException('Entity field does not contain state information.');
    }
    $currentState = $field->getValue()[0]['value'];
    if (count($parts) === 4 && $parts[3] !== $currentState) {
      throw new \InvalidArgumentException('Entity state does not match the transition from state.');
    }
    /** @var \Drupal\state_machine\WorkflowManager $manager */
    $manager = \Drupal::service('plugin.manager.workflow');
    /** @var \Drupal\state_machine\Plugin\Workflow\Workflow $workflow */
    $workflow = $manager->createInstance($parts[0]);
    if (!$workflow) {
      throw new \InvalidArgumentException('Entity state field is not attached to the selected workflow.');
    }
    if (!$workflow->findTransition($currentState, $parts[2])) {
      throw new \InvalidArgumentException('Requested transition not allowed.');
    }
    $field->setValue($parts[2]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
      'transition_id' => '',
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
      foreach ($workflow->getTransitions() as $transition_id => $transition) {
        $toState = $transition->getToState();
        $options[$wid . '-' . $transition_id . '-' . $toState->getId()] = $workflow->getLabel() . ': ' . $transition->getLabel();
        foreach ($transition->getFromStates() as $fromState) {
          $options[$wid . '-' . $transition_id . '-' . $toState->getId() . '-' . $fromState->getId()] = $workflow->getLabel() . ': ' . $transition->getLabel() . ' from ' . $fromState->getLabel();
        }
      }
    }
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    $form['transition_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#options' => $options,
      '#default_value' => $this->configuration['transition_id'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    $this->configuration['transition_id'] = $form_state->getValue('transition_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
