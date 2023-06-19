<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\server_general\LinkTrait;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\ElementNodeGroupTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\ElementLayoutTrait;
use Drupal\server_general\TitleAndLabelsTrait;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {

  use ElementNodeGroupTrait;
  use TitleAndLabelsTrait;
  use ElementLayoutTrait;

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
    // The node's label.
    $bundle = $entity->bundle();
    $label = NodeType::load($bundle)->label();
    $element = $this->buildElementNodeGroup(
      $entity,
      $label,
      $this->currentUser,
      $entity->toUrl('canonical', ['absolute' => TRUE])
    );
    $build[] = $element;

    return $build;
  }

  /**
   * @param string $entity_type
   * @param NodeInterface $entity
   * @return string
   */
  protected function getSubscriptionUrl(NodeInterface $entity): string {
    $entity_type = $entity->getEntityType()->id();

    $sub_url = Url::fromRoute('og.subscribe', [
      'entity_type_id' => $entity_type,
      'group' => $entity->id(),
      'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
    ]);
    return $sub_url->toString();
  }

}
