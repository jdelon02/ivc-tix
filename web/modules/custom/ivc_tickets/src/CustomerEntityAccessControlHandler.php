<?php

namespace Drupal\ivc_tickets;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Customer entity.
 *
 * @see \Drupal\ivc_tickets\Entity\CustomerEntity.
 */
class CustomerEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ivc_tickets\Entity\CustomerEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished customer entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published customer entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit customer entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete customer entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add customer entities');
  }


}
