<?php

namespace Drupal\moodle_rest_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\migrate\Row;
use Drupal\moodle_rest\Services\MoodleRestException;
use Drupal\moodle_rest\Services\RestFunctions;
use Drupal\moodle_rest_user\Event\MoodleUserAssociate;
use Drupal\moodle_rest_user\Event\MoodleUserMap;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Helper class for User CRUD hooks.
 */
class UserEventHelper implements ContainerInjectionInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Moodle Rest Functions connector.
   *
   * @var \Drupal\moodle_rest\Services\RestFunctions
   */
  protected $moodle;

  /**
   * User Event Helper constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\moodle_rest\Services\RestFunctions $moodle
   *   The Moodle REST Functions connector.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, RestFunctions $moodle) {
    $this->eventDispatcher = $event_dispatcher;
    $this->settings = $config_factory->get('moodle_rest_user.settings');
    $this->moodle = $moodle;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('config.factory'),
      $container->get('moodle_rest.rest_functions')
    );
  }

  /**
   * Operate on hook_user_login().
   *
   * @see moodle_rest_user_user_login()
   */
  public function userLogin(UserInterface $account) {
    if (
      $this->isUpdating($account) ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }
    $updated = FALSE;
    if (!$moodle_id && $moodle_id = $this->settings->get('associate')) {
      if ($moodle_id = $this->associateAccount($account)) {
        $account->get($this->settings->get('moodle_id_field'))->setValue($moodle_id);
        $updated = TRUE;
      }
    }

    if ($moodle_id && $this->settings->get('pull')['login']) {
      $pulled_account = $this->pullUser($account, $moodle_id);
      if ($pulled_account && $pulled_account !== $account) {
        $account = $pulled_account;
        $updated = TRUE;
      }
    }

    if ($updated) {
      $account->moodle_rest_update = TRUE;
      $account->save();
    }
  }

  /**
   * Operate on hook_user_presave().
   *
   * @see moodle_rest_user_user_presave()
   */
  public function userPresave(UserInterface $account) {
    if (
      $this->isUpdating($account) ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }

    if (!$moodle_id && $this->settings->get('associate')) {
      if ($moodle_id = $this->associateAccount($account)) {
        $account->get($this->settings->get('moodle_id_field'))->setValue($moodle_id);
      }
    }
  }

  /**
   * Operate on hook_user_insert().
   *
   * @see moodle_rest_user_user_insert()
   */
  public function userInsert(UserInterface $account) {
    if (
      $this->isUpdating($account) ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }

    if (!$moodle_id && $this->settings->get('create')) {
      $variable=1;
      if ($moodle_id = $this->createMoodleUser($account)) {
        $account->get($this->settings->get('moodle_id_field'))->setValue($moodle_id);
        $account->moodle_rest_update = TRUE;
        $account->save();
      }
    }
  }

  /**
   * Operate on hook_user_update().
   *
   * @see moodle_rest_user_user_update()
   */
  public function userUpdate(UserInterface $account) {
    if (
      $this->isUpdating($account) ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }

    if ($moodle_id && $this->settings->get('update')) {
      $moodle_id = $this->pushUser($account, $moodle_id);
    }
  }

  /**
   * Operate on hook_user_prepare_form().
   *
   * @see moodle_rest_user_user_prepare_form()
   */
  public function userEdit(UserInterface $account, FormStateInterface $form_state) {
    if (
      !$this->settings->get('pull')['edit'] ||
      $this->isUpdating($account) ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }

    if (!$moodle_id && $this->settings->get('associate')) {
      if ($moodle_id = $this->associateAccount($account)) {
        $account->get($this->settings->get('moodle_id_field'))->setValue($moodle_id);
      }
    }

    if ($moodle_id) {
      $pulled_account = $this->pullUser($account, $moodle_id);
      // @todo rebuild form with $pulled_account.
    }
  }

  /**
   * Operate on hook_user_view().
   *
   * @see moodle_rest_user_user_view()
   */
  public function userView(UserInterface $account, array $build) {
    if (
      !$this->settings->get('pull')['view'] ||
      ($moodle_id = $this->getMoodleId($account)) === FALSE
    ) {
      return;
    }

    $updated = FALSE;
    if (!$moodle_id && $this->settings->get('associate')) {
      if ($moodle_id = $this->associateAccount($account)) {
        $account->get($this->settings->get('moodle_id_field'))->setValue($moodle_id);
        $updated = TRUE;
      }
    }

    if ($moodle_id && $this->settings->get('pull')['edit']) {
      $pulled_account = $this->pullUser($account, $moodle_id);
      if ($pulled_account && $pulled_account !== $account) {
        $account = $pulled_account;
        $updated = TRUE;
      }
      // @todo update $build with $pulled_account.
    }

    if ($updated) {
      $account->moodle_rest_update = TRUE;
      $account->save();
    }
  }

  /**
   * User entity is saved for a moodle update.
   *
   * @return bool
   */
  private function isUpdating(UserInterface $account) {
    return !empty($account->moodle_rest_update);
  }

  /**
   * Get Moodle ID.
   *
   * @return int|bool
   */
  public function getMoodleId(UserInterface $account) {
    $moodle_field = $account->get($this->settings->get('moodle_id_field'));
    if (!$moodle_field) {
      return FALSE;
    }
    return (int) $moodle_field->isEmpty() ? 0 : $moodle_field->first()->getValue()['value'];
  }

  /**
   * Try to associate user with existing moodle account.
   */
  public function associateAccount(UserInterface $account): int {
    $event = new MoodleUserAssociate($account);
    $this->eventDispatcher->dispatch(MoodleUserAssociate::EVENT_NAME, $event);
    return $event->moodleId;
  }

  /**
   * Push a new user into Moodle.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user to be added to Moodle.
   */
  protected function createMoodleUser(UserInterface $account) {
    $mapping = $this->settings->get('push_fields');
    $source = $this->userSourceFromMapping($account, $mapping);
    $row = new Row($source, array_flip(array_column($mapping, 'drupal')));
    // @todo Map can throw an exception for missing fields?
    $event = new MoodleUserMap($row, $mapping);
    $this->eventDispatcher->dispatch(MoodleUserMap::PUSH_EVENT, $event);
    try {
      $result = $this->moodle->createUsers([$event->row->getDestination()]);
      $result = reset($result);
      if ($moodle_id = $result['id']) {
        return $moodle_id;
      }
    }
    catch (MoodleRestException $e) {
      // @todo Notify user? Log?
    }
  }

  /**
   * Pull user fields from Moodle to a user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to be pulled.
   * @param int $moodle_id
   *   Moodle ID of account.
   */
  protected function pullUser(UserInterface $account, $moodle_id) {
    try {
      $result = $this->moodle->getUsers(['id' => $moodle_id]);
    }
    catch (MoodleRestException $e) {
      // @todo Notify user? Log?
      return;
    }

    $update_account = clone $account;
    if ($moodle_user = reset($result)) {
      $mapping = $this->settings->get('pull_fields');
      $row = new Row($moodle_user, array_flip(array_column($mapping, 'moodle')));
      $event = new MoodleUserMap($row, $mapping);
      $this->eventDispatcher->dispatch(MoodleUserMap::PULL_EVENT, $event);
      foreach ($row->getDestination() as $field_name => $values) {
        $field = $update_account->$field_name;
        if ($field instanceof TypedDataInterface) {
          $field->setValue($values);
        }
      }
    }

    return $update_account;
  }

  /**
   * Push user fields to a Moodle user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user accout to push.
   * @param int $moodle_id
   *   Moodle ID of account.
   */
  protected function pushUser(UserInterface $account, $moodle_id) {
    $mapping = $this->settings->get('push_fields');
    $source = $this->userSourceFromMapping($account, $mapping);
    $row = new Row($source, array_flip(array_column($mapping, 'drupal')));
    // @todo Map can throw an exception for missing fields?
    $event = new MoodleUserMap($row, $mapping);
    $this->eventDispatcher->dispatch(MoodleUserMap::PUSH_EVENT, $event);
    try {
      $mapped_fields = $event->row->getDestination();
      $mapped_fields['id'] = $moodle_id;
      $result = $this->moodle->updateUsers([$mapped_fields]);
    }
    catch (MoodleRestException $e) {
      // @todo Notify user? Log?
    }
  }

  protected function userSourceFromMapping(UserInterface $user, array $mapping) {
    $fields = array_map(function ($value) {
      return strstr($value['drupal'] . '/', '/', TRUE);
    }, $mapping);

    $source = [];
    foreach ($fields as $field_name) {
      $field = $user->{$field_name};
      if ($field instanceof TypedDataInterface) {
        if ($field->getDataDefinition()->isList()) {
          $source[$field_name] = $field->getValue();
        }
        else {
          $source[$field_name] = $field->value;
        }
      }
    }

    return $source;
  }

}
