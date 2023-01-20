<?php

namespace Drupal\eca_base;

use Drupal\eca\EcaState;
use Drupal\eca\Event\BaseHookHandler;

/**
 * The handler for hook implementations within the eca_base.module file.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class HookHandler extends BaseHookHandler {

  /**
   * The ECA state service.
   *
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $state;

  /**
   * Set the ECA state service.
   *
   * @param \Drupal\eca\EcaState $state
   *   The ECA state service.
   */
  public function setState(EcaState $state) {
    $this->state = $state;
  }

  /**
   * Trigger ECA's cron event.
   */
  public function cron(): void {
    $this->triggerEvent->dispatchFromPlugin('eca_base:eca_cron', $this->state);
  }

}
