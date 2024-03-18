<?php

namespace Drupal\server_general;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Trait LockedLandingPagesTrait.
 *
 * Helper method for locking Landing Pages.
 */
class LockedLandingPages {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LockedPages object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns the 'main_settings' config page.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The 'main_settings' config page entity or null if not found.
   */
  public function getMainSettings() {
    /** @var \Drupal\config_pages\ConfigPagesStorage $config_pages_storage */
    $config_pages_storage = $this->entityTypeManager->getStorage('config_pages');
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $main_settings */
    $main_settings = $config_pages_storage->load('main_settings');
    return $main_settings;
  }

  /**
   * Checks if current node is locked.
   *
   * @return bool
   *   Returns TRUE if node is locked.
   */
  public function isNodeLocked(NodeInterface $node) {
    $restricted_nodes = $this->getRestrictedNodes();

    return in_array($node->id(), $restricted_nodes);
  }

  /**
   * Returns list of restricted node ids from config pages.
   *
   * @return array
   *   List of ids.
   */
  protected function getRestrictedNodes() {
    $main_settings = $this->getMainSettings();

    if (!$main_settings instanceof ConfigPages) {
      return [];
    }

    $locked_nodes = $main_settings->get('field_locked_landing_pages')->getValue();

    return array_column($locked_nodes, 'target_id');
  }

}
