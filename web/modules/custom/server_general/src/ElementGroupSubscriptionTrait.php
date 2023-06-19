<?php

namespace Drupal\server_general;

use Drupal\Core\Session\AccountProxy;
use Drupal\og\Og;

/**
 * Helper methods for getting themed social share buttons.
 */
trait ElementGroupSubscriptionTrait {

  /**
   * Build the social media buttons.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that's being shared.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildElementGroupSubscription(string $userName, string $sub_url, $label): array {
    return [
      '#markup' => t('Hi @name, <em><a href="@path">click here</a></em> if you would like to subscribe to this group called "<em>@label</em>".', ['@name' => $userName, '@path' => $sub_url, '@label' => $label]),
    ];
  }

  /**
   * Check if the use has access to subscribe to the group.
   *
   * @param AccountProxy $user
   *   Current user.
   * @param $entity
   *   Group entity.
   *
   * @return bool
   * Returns TRUE if the current user has access subscribe, otherwise FALSE.
   */
  protected function checkSubscriptionAccess(AccountProxy $user, $entity): bool {
    $entity_type = $entity->getEntityType()->id();
    $bundle = $entity->bundle();
    // Reject access if the node is not a group, or the current user is
    // anonymous, blocked or has an active subscription or a pending request.
    if (!Og::isGroup($entity_type, $bundle) ||
      $user->isAnonymous() ||
      Og::isMemberBlocked($entity, $user) ||
      Og::isMemberPending($entity, $user) ||
      Og::isMember($entity, $user)) {
      return FALSE;
    }
    return TRUE;
  }

}
