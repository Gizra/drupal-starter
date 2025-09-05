<?php

namespace Drupal\server_general\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for handling group subscription requests.
 */
class GroupSubscribeController extends ControllerBase {

  /**
   * Subscribe the current user to a given Group node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Group node.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the group page.
   */
  public function subscribe(NodeInterface $node) {
    $current_user = $this->currentUser();
    
    if ($current_user->isAuthenticated()) {
      $account = $this->entityTypeManager()
        ->getStorage('user')
        ->load($current_user->id());
      
      // Get the correct OG services
      /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
      $membership_manager = \Drupal::service('og.membership_manager');
      
      /** @var \Drupal\og\OgAccessInterface $og_access */
      $og_access = \Drupal::service('og.access');
      
      // Check if user is already a member
      $is_member = $membership_manager->isMember($node, $current_user->id());
      
      if (!$is_member) {
        // Check if user is allowed to subscribe
        $can_subscribe = $og_access->userAccess($node, 'subscribe', $account)->isAllowed();
        
        if ($can_subscribe) {
          // Create and save membership
          $membership = $membership_manager->createMembership($node, $account);
          $membership->save();
          
          $this->messenger()->addMessage($this->t('You have successfully subscribed to the group.'));
        }
        else {
          $this->messenger()->addError($this->t('You do not have permission to subscribe to this group.'));
        }
      }
      else {
        $this->messenger()->addMessage($this->t('You are already a member of this group.'));
      }
    }
    else {
      $this->messenger()->addError($this->t('You must be logged in to subscribe to a group.'));
    }
    
    // Redirect back to the group node page.
    return new RedirectResponse($node->toUrl()->toString());
  }
}