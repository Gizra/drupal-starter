<?php

namespace Drupal\og_custom_work\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;

/**
 * The "Node Organic Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Organic Group"),
 *   description = "Node view builder for Organic Group."
 * )
 */
class NodeOrganicGroup extends EntityViewBuilderPluginAbstract {

  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user, EntityRepositoryInterface $entity_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $entity_type_manager, $current_user, $entity_repository);
  }


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
  public function buildFull(array $build, NodeInterface $entity): array {
    if ($this->currentUser->isAuthenticated()) {
      $element = [
        '#theme' => 'og_custom_work_group_body',
        '#name' => $this->currentUser->getDisplayName(),
        '#label' => $entity->label(),
      ];

      $build[] = $element;
    }
    return $build;
  }
}
