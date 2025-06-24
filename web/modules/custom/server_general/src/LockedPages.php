<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\node\NodeInterface;

/**
 * Class LockedPages.
 *
 * The Locked Pages service.
 */
class LockedPages {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new LockedPages object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Returns the 'main_settings' config page.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The 'main_settings' config page entity or null if not found.
   */
  public function getMainSettings(): ?ContentEntityInterface {
    /** @var \Drupal\config_pages\ConfigPagesStorage $config_pages_storage */
    $config_pages_storage = $this->entityTypeManager->getStorage('config_pages');
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $main_settings */
    $main_settings = $config_pages_storage->load('main_settings');
    return $main_settings;
  }

  /**
   * Checks if current entity is locked.
   *
   * @return bool
   *   Returns TRUE if entity is locked.
   */
  public function isNodeLocked(NodeInterface $node): bool {
    $restricted_nodes = $this->getRestrictedNodes();
    return in_array($node->id(), $restricted_nodes);
  }

  /**
   * Returns list of restricted entity ids from config pages.
   *
   * @return array
   *   List of ids.
   */
  protected function getRestrictedNodes(): array {
    $main_settings = $this->getMainSettings();

    if (!$main_settings instanceof ConfigPages) {
      return [];
    }

    $locked_entities = $main_settings->get('field_locked_pages')->getValue();

    return array_column($locked_entities, 'target_id');
  }

  /**
   * Returns the list of bundles that can be referenced.
   *
   * @return array
   *   An array of bundle type machine names.
   */
  public function getReferencedBundles(): array {
    // Load field definition for field_locked_pages field.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('config_pages', 'main_settings');

    // Check if the field definition exists and it's an entity reference.
    if (isset($field_definitions['field_locked_pages']) && $field_definitions['field_locked_pages'] instanceof FieldDefinitionInterface && $field_definitions['field_locked_pages']->getType() == 'entity_reference') {
      $handler_settings = $field_definitions['field_locked_pages']->getSetting('handler_settings');

      // Get the target bundle type from handler settings.
      if (!empty($handler_settings['target_bundles'])) {
        return array_values($handler_settings['target_bundles']);
      }
    }

    return [];
  }

}
