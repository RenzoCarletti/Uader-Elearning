<?php

namespace Drupal\eca_state_machine\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 * State Machine event deriver.
 */
class StateMachineEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function definitions(): array {
    return StateMachineEvent::definitions();
  }

}
