<?php

namespace Drupal\moodle_rest_user\Commands;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\moodle_rest_user\UserBatchHelper;
use Drush\Commands\DrushCommands;

/**
 * A Drush commands for Moodle User.
 */
class MoodleDrushCommands extends DrushCommands {

  /**
   * Moodle Batch helper class.
   *
   * @var \Drupal\moodle_rest_user\UserBatchHelper
   */
  protected $batchHelper;

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClassResolverInterface $class_resolver, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->batchHelper = $class_resolver->getInstanceFromDefinition(UserBatchHelper::class);
    $this->entityTypeManager = $entityTypeManager;
    $this->setLogger($loggerChannelFactory->get('issup_moodle'));
  }

  /**
   * Associate Drupal users with external Moodle IDs.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option uid
   *   Optional comma seperated of user ids to update. All users otherwise
   *   processed.
   * @option update
   *   Overwrite any stored existing Moodle IDs.
   * @usage issup-moodle:associate-id --uid=4 --update=TRUE
   *   Update User 4's associated Moodle ID.
   *
   * @command issup-moodle:associate-id
   * @aliases moodle-ids
   */
  public function associateIds(array $options = ['uid' => '', 'update' => FALSE]) {
    $this->logger()->info('Associate Drupal Moodle Users.');

    if (!empty($options['uid'])) {
      $uids = explode(',', $options['uid']);
      foreach ($uids as $uid) {
        $result = $this->batchHelper->associateAccountById($uid, $options['update']);
        $this->logger()->notice("$uid $result.");
      }
    }
    else {
      $operations[] = [
        [UserBatchHelper::class, 'associateUsersBatchCallback'],
        [$options['update']],
      ];
      $batch = [
        'title' => \t('Associating users'),
        'operations' => $operations,
        'finished' => [UserBatchHelper::class, 'associateUsersBatchFinished'],
      ];
      \batch_set($batch);
      \drush_backend_batch_process();
    }

    $this->logger()->notice("Completed associating users.");
  }

}
