<?php

namespace Drupal\server_og\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
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
final class NodeGroup extends EntityViewBuilderPluginAbstract {

  /**
   * Build full view mode.
   */
  public function buildFull(array $build, NodeInterface $entity): array {

    // Only allow if OG content.
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      return $build;
    }

    // @todo: Add DI.
    $current_user = \Drupal::currentUser();

    // Anonymous: only invite to subscribe (no body).
    if ($current_user->isAnonymous()) {
      $build['og_subscribe_prompt'] = [
        '#markup' => $this->t('You must be an authenticated user and be in this group to view the content.'),
      ];
      return $build;
    }

    // @todo: Add DI.
    $membership_manager = \Drupal::service('og.membership_manager');
    // If authenticated user is NOT member of OG then show link to subscribe.
    if (!$membership_manager->isMember($entity, $current_user)) {
      // Build subscribe prompt.
      $join_url = Url::fromRoute('server_og.group_join', [
        'node' => $entity->id(),
      ], ['query' => ['destination' => \Drupal::service('path.current')->getPath()]]);

      $build['og_subscribe_prompt'] = [
        '#theme' => 'server_og_subscribe_prompt',
        '#name' => $current_user->getDisplayName(),
        '#label' => $entity->label(),
        '#url' => $join_url->toString(),
      ];

    }
    // If it is a member then show the rest of the fields.
    else {
      // @todo: implement methods for rendering the rest of the fields.
      $build[] = ['#markup' => $entity->label()];
      return $build;
    }
    return $build;
  }

}
