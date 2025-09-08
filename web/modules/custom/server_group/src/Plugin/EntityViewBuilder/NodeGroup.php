<?php

namespace Drupal\server_group\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends EntityViewBuilderPluginAbstract {

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {

    // Offer subscription to registered users.
    $current_user = \Drupal::currentUser();
    $group = $entity;
    if ($current_user->isAuthenticated()) {
      $og_group_type_manager = \Drupal::service('og.group_type_manager');
      $og_access = \Drupal::service('og.access');
      $user = \Drupal\user\Entity\User::load($current_user->id());

      // Check if user can subscribe (join) this group.
      if ($og_group_type_manager->isGroup($group->getEntityTypeId(), $group->bundle()) && 
          $og_access->userAccess($group, 'subscribe', $user)->isAllowed()) {
        
        $is_member = \Drupal::service('og.membership_manager')->isMember($group, $user);
        $name = $user->getDisplayName();
        $label = $group->label();
        if (!$is_member) {
            $build[] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['og-subscribe-greeting']],
            'greeting' => [
                '#markup' => '<h1>Group: ' . $group->label() . '</h1><p>' . $this->t('Hi @name, <br> Welcome to the @label group! You are member of this group', [
                '@name' => $name,
                '@label' => $label,
                ]) . '</p>',
            ],
            ];
        }
        else {
            $subscribe_url = \Drupal\Core\Url::fromRoute('og.subscribe', [
            'entity_type_id' => $group->getEntityTypeId(),
            'group' => $group->id(),
            ])->toString();

            $build[] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['og-subscribe-greeting']],
            'greeting' => [
                '#markup' => '<h1>Group: ' . $group->label() . '</h1><p>' . $this->t('Hi @name, <br><a href="@url">click here</a> if you would like to subscribe to this group called @label.', [
                '@name' => $name,
                '@url' => $subscribe_url,
                '@label' => $label,
                ]) . '</p>',
            ],
            ];
        }
      }
    }
    else {
        $build[] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['og-subscribe-greeting']],
          'greeting' => [
            '#markup' => '<h1>' . $this->t('Group: @label', ['@label' => $group->label()]) . '</h1><p>' . $this->t('Please <a href="/user/login">log in</a> or <a href="/user/register">register</a> to join this group and participate in discussions.') . '</p>',
          ],
        ];
    }

    return $build;
  }
}