<?php

namespace Drupal\eca_state_machine\Plugin\ECA\Condition;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Plugin implementation of the ECA condition for State Machine transitions.
 *
 * @EcaCondition(
 *   id = "eca_state_machine_workflow_transition",
 *   label = @Translation("State Machine: WorkflowTransition")
 * )
 */
class StateMachineCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $event = $this->getEvent();
    if ($event instanceof WorkflowTransitionEvent) {
      $parts = explode('-', $this->configuration['transition_id']);
      if ($parts[0] === $event->getWorkflow()->getId()) {
        if (count($parts) === 1) {
          return TRUE;
        }
        if ($parts[1] === $event->getTransition()->getToState()->getId()) {
          if (count($parts) === 2) {
            return TRUE;
          }
          return $parts[2] === $event->getEntity()->original->get($event->getFieldName())->getValue()[0]['value'];
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
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
      $options[$wid] = $workflow->getLabel();
      foreach ($workflow->getTransitions() as $transition) {
        $toStateId = $transition->getToState()->getId();
        $options[$wid . '-' . $toStateId] = $workflow->getLabel() . ': ' . $transition->getLabel();
        foreach ($transition->getFromStates() as $fromState) {
          $options[$wid . '-' . $toStateId . '-' . $fromState->getId()] = $workflow->getLabel() . ': ' . $transition->getLabel() . ' from ' . $fromState->getLabel();
        }
      }
    }
    $form['transition_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#options' => $options,
      '#default_value' => $this->configuration['transition_id'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['transition_id'] = $form_state->getValue('transition_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
