<?php

namespace Drupal\server_general;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class NodeViewBuilderPerBundleAbstract.
 */
class NodeViewBuilderAbstract {

  use ComponentWrapTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * NodeViewBuilderCollection constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, AccountInterface $current_user, BlockManagerInterface $block_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->blockManager = $block_manager;
  }

  /**
   * Default build in "Card" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return mixed[]
   *   An array of elements for output on the page.
   */
  public function buildCard(array $build, NodeInterface $entity) {
    $build += $this->getElementBase($entity);
    $build['#theme'] = 'server_theme_card';

    return $build;
  }

  /**
   * Get common elements for the view modes.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array[]
   *   A renderable array.
   */
  protected function getElementBase(NodeInterface $entity) {
    $element = [];
    $element['#nid'] = $entity->id();
    $element['#title'] = $entity->label();
    $element['#url'] = $entity->toUrl()->toString();

    return $element;
  }

}
