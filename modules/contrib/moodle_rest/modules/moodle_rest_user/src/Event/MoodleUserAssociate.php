<?php

namespace Drupal\moodle_rest_user\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A matching Moodle acount is trying to be found for a user entity.
 *
 * There is one subscriber in the Moodle REST user module to syncronise by email
 * address, there may however be for example a Moodle Custom field containing
 * the Drupal ID or other shared external data. Use an event subscriber which is
 * trigged @todo.
 */
class MoodleUserAssociate extends Event {

  public const EVENT_NAME = 'moodle_rest_user.associate';

  /**
   * The present best associated Moodle account ID.
   *
   * @var int
   */
  public $moodleId = 0;

  /**
   * The Drupal user account being associated.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $account;

  /**
   * Event constructor.
   *
   * @param \Drupal\User\UserInterface $account
   *   The Drupal user account being associated.
   */
  public function __construct(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * Get user account being associated.
   *
   * @return \Drupal\User\UserInterface
   *   User account being associated.
   */
  public function getAccount(): UserInterface {
    return $this->account;
  }

}
