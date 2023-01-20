<?php

namespace Drupal\eca_access\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityEventInterface;

/**
 * Dispatched when an entity is being asked for access.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class EntityAccess extends Event implements ConditionalApplianceInterface, EntityEventInterface {

  /**
   * The entity being asked for access.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected EntityInterface $entity;

  /**
   * The operation to perform.
   *
   * @var string
   */
  protected string $operation;

  /**
   * The account that asks for access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|null
   */
  protected ?AccessResultInterface $accessResult = NULL;

  /**
   * Constructs a new EntityAccess object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being asked for access.
   * @param string $operation
   *   The operation to perform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that asks for access.
   */
  public function __construct(EntityInterface $entity, string $operation, AccountInterface $account) {
    $this->entity = $entity;
    $this->operation = $operation;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the operation to perform.
   *
   * @return string
   *   The operation.
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * Get the account that asks for access.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account.
   */
  public function getAccount(): AccountInterface {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_entity_type_ids, $w_bundles, $w_operations] = explode(':', $wildcard);

    if (($w_entity_type_ids !== '*') && !in_array($this->getEntity()->getEntityTypeId(), explode(',', $w_entity_type_ids), TRUE)) {
      return FALSE;
    }

    if (($w_bundles !== '*') && !in_array($this->getEntity()->bundle(), explode(',', $w_bundles), TRUE)) {
      return FALSE;
    }

    if (($w_operations !== '*') && !in_array($this->getOperation(), explode(',', $w_operations), TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    if (!empty($arguments['entity_type_id']) && $arguments['entity_type_id'] !== '*') {
      $contains_entity_type_id = FALSE;
      foreach (explode(',', $arguments['entity_type_id']) as $c_entity_type_id) {
        $c_entity_type_id = strtolower(trim($c_entity_type_id));
        if ($contains_entity_type_id = ($c_entity_type_id === $this->getEntity()->getEntityTypeId())) {
          break;
        }
      }
      if (!$contains_entity_type_id) {
        return FALSE;
      }
    }

    if (!empty($arguments['bundle']) && $arguments['bundle'] !== '*') {
      $contains_bundle = FALSE;
      foreach (explode(',', $arguments['bundle']) as $c_bundle) {
        $c_bundle = strtolower(trim($c_bundle));
        if ($contains_bundle = ($c_bundle === $this->getEntity()->bundle())) {
          break;
        }
      }
      if (!$contains_bundle) {
        return FALSE;
      }
    }

    if (!empty($arguments['operation']) && $arguments['operation'] !== '*') {
      $contains_operation = FALSE;
      foreach (explode(',', $arguments['operation']) as $c_operation) {
        $c_operation = trim($c_operation);
        if ($contains_operation = ($c_operation === $this->getOperation())) {
          break;
        }
      }
      if (!$contains_operation) {
        return FALSE;
      }
    }

    // Initialize with a neutral result.
    $this->accessResult = AccessResult::neutral();

    return TRUE;
  }

  /**
   * Get the access result.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   The access result, or NULL if no result was calculated.
   */
  public function getAccessResult(): ?AccessResultInterface {
    return $this->accessResult;
  }

  /**
   * Set the access result.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $result
   *   The access result to set.
   *
   * @return $this
   */
  public function setAccessResult(AccessResultInterface $result): EntityAccess {
    $this->accessResult = $result;
    return $this;
  }

}
