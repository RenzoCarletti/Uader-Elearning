<?php

namespace Drupal\eca_state_machine\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * Plugin implementation of the ECA Events for state_machine.
 *
 * @EcaEvent(
 *   id = "state_machine",
 *   deriver = "Drupal\eca_state_machine\Plugin\ECA\Event\StateMachineEventDeriver"
 * )
 */
class StateMachineEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [];

    foreach (['pre_transition', 'post_transition'] as $phase) {
      $actions['state_machine.' . $phase] = [
        'label' => 'State Machine: ' . $phase,
        'event_name' => 'state_machine.' . $phase,
        'event_class' => WorkflowTransitionEvent::class,
      ];
    }

    return $actions;
  }

}
