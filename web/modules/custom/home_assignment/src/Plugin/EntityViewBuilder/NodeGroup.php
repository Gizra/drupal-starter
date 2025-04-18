<?php

namespace Drupal\home_assignment\Plugin\EntityViewBuilder;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;

/**
 * The "Group" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for 'Group' bundle."
 * )
 */
class NodeGroup extends EntityViewBuilderPluginAbstract
{

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
  public function buildFull(array $build, NodeInterface $entity): array
  {
    $user = \Drupal::currentUser();
    $is_member = Og::isMember($entity, $user);

    if (!$is_member) {
      $url = Url::fromRoute('og.subscribe', [
        'entity_type_id' => $entity->getEntityTypeId(),
        'group' => $entity->id(),
        'og_membership_type' => 'default',
      ]);

      $build['offer'] = [
        '#markup' => new FormattableMarkup('Hi @name, click here if you would like to <a href=":href">subscribe</a> to this group called @label?', [
          '@name' => 'name',
          '@label' => 'label',
          ':href' => $url->toString(),
        ]),
      ];
    }

    return $build;
  }


}
