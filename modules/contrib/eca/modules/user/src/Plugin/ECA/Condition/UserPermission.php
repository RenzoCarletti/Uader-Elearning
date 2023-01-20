<?php

namespace Drupal\eca_user\Plugin\ECA\Condition;

/**
 * Plugin implementation of the ECA condition of any user's permissions.
 *
 * @EcaCondition(
 *   id = "eca_user_permission",
 *   label = "User has permission"
 * )
 */
class UserPermission extends CurrentUserPermission {

  use UserTrait;

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if ($account = $this->loadUserAccount()) {
      return $this->negationCheck($account->hasPermission($this->configuration['permission']));
    }
    return FALSE;
  }

}
