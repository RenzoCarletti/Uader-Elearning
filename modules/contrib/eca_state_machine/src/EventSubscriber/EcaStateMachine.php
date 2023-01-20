<?php

namespace Drupal\eca_state_machine\EventSubscriber;

use Drupal\eca\EcaEvents;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_state_machine\Plugin\ECA\Event\StateMachineEvent;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

/**
 * ECA state_machine event subscriber.
 */
class EcaStateMachine extends EcaBase {

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    if ($event instanceof WorkflowTransitionEvent) {
      $this->tokenService->addTokenData('entity', $event->getEntity());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (StateMachineEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    return $events;
  }

}
