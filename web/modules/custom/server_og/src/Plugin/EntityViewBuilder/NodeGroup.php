<?php

namespace Drupal\server_og\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;

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

  use ProcessedTextBuilderTrait;

  /**
   * Build full view mode.
   */
  public function buildFull(array $build, NodeInterface $entity): array {
    $current_user = \Drupal::currentUser();

    // Only for OG groups.
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      // Not a group: render normally.
      $build[] = $this->buildProcessedText($entity);
      return $build;
    }

    // Anonymous: only invite to subscribe (no body).
    if ($current_user->isAnonymous()) {
      $build['og_subscribe_prompt'] = [
        '#markup' => $this->t('You must be an authenticated user and be in this group ' . $entity->label() . ' to view the content.'),
        '#weight' => -1000,
        '#cache' => [
          'contexts' => ['user'],
          'tags' => $entity->getCacheTags(),
        ],
      ];
      return $build;
    }

    $membership_manager = \Drupal::service('og.membership_manager');
    $is_member = $membership_manager->isMember($entity, $current_user);

    if (!$is_member) {
      // Build subscribe prompt….
      $join_url = Url::fromRoute('server_og.group_join', [
        'node' => $entity->id(),
      ], ['query' => ['destination' => \Drupal::service('path.current')->getPath()]]);

      $build['og_subscribe_prompt'] = [
        '#theme'  => 'server_og_subscribe_prompt',
        '#name'   => $current_user->getDisplayName(),
        '#label'  => $entity->label(),
        '#url'    => $join_url->toString(),
        '#weight' => -1000,
        '#cache'  => ['contexts' => ['user'], 'tags' => $entity->getCacheTags()],
      ];

      return $build;
    }

    $build[] = $this->buildProcessedText($entity);
    return $build;
  }

}
