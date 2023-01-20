<?php

namespace Drupal\moodle_rest_user\EventSubscriber;

use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\moodle_rest\Services\RestFunctions;
use Drupal\moodle_rest_user\Event\MoodleUserAssociate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Associate Uset to Moodle with email address.
 */
class AssociateEventSubscriber implements EventSubscriberInterface {

  /**
   * Moodle REST functions.
   *
   * @var \Drupal\moodle_rest\Services\RestFunctions
   */
  protected $moodle;

  /**
   * Constructor.
   */
  public function __construct(RestFunctions $moodle) {
    $this->moodle = $moodle;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MoodleUserAssociate::EVENT_NAME => 'associateByEmail',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\moodle_rest_user\Event\MoodleUserAssociate $event
   *   User association event.
   */
  public function associateByEmail(MoodleUserAssociate $event) {
    // If an earlier subscriber has found an ID we don't run.
    if (!$event->moodleId) {
      // Search by email.
      $account = $event->getAccount();
      if ($email = $account->getEmail()) {
        try {
          $users = $this->moodle->getUsersByField('email', [$email]);
        }
        catch (MoodleRestException $e) {
          \watchdog_exception('moodle_rest_user', $e);
        }
      }
      if (!empty($users)) {
        $user = reset($users);
        $event->moodleId = $user['id'];
      }
    }
  }

}
