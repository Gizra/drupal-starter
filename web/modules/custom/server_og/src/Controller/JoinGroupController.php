<?php

namespace Drupal\server_og\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for handling the "join group" action for OG groups.
 *
 * This controller ensures that the current user:
 * - Is authenticated.
 * - The node is a valid Organic Groups (OG) group.
 * - Has permission to subscribe/create membership.
 *
 * If those conditions are met, it creates a new OG membership and redirects
 * the user back to the requested destination or to the group node page.
 */
final class JoinGroupController extends ControllerBase {

  /**
   * Allows the current user to join an OG group.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that represents the group.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the requested destination (if available)
   *   or to the group node page.
   */
  public function join(NodeInterface $node): RedirectResponse {
    $account = $this->currentUser();

    // Validate that the user is logged in and the node is a valid OG group.
    if ($account->isAnonymous() || !Og::isGroup($node->getEntityTypeId(), $node->bundle())) {
      $this->messenger()->addError($this->t('Not allowed.'));
      return $this->redirect('<front>');
    }

    // Get OG services: membership manager and access checker.
    $membership_manager = \Drupal::service('og.membership_manager');
    $og_access = \Drupal::service('og.access');

    // If the user is already a member, nothing new is created.
    if ($membership_manager->isMember($node, $account)) {
      $this->messenger()->addStatus($this->t('You are already a member of this group.'));
      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }

    // Check OG permissions to allow subscribing to the group.
    $allowed = $og_access->userAccess($node, 'subscribe', $account)->isAllowed()
      || $og_access->userAccess($node, 'create og membership', $account)->isAllowed();

    if (!$allowed) {
      $this->messenger()->addError($this->t('You are not allowed to subscribe to this group.'));
      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }

    // Load the full user entity (createMembership requires UserInterface).
    $user_storage = $this->entityTypeManager()->getStorage('user');
    $user = $user_storage->load($account->id());
    if (!$user) {
      $this->messenger()->addError($this->t('User not found.'));
      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }

    // Create an active membership for this group and user.
    $membership = $membership_manager->createMembership($node, $user);
    $membership->save();

    $this->messenger()->addStatus($this->t('Welcome! You have subscribed to %label.', ['%label' => $node->label()]));

    // Redirect to the requested destination (if provided), or back to the group.
    $destination = \Drupal::service('redirect.destination')->get();
    if (!empty($destination)) {
      return new RedirectResponse(
        Url::fromUserInput($destination)->toString()
      );
    }
    return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
  }

}
