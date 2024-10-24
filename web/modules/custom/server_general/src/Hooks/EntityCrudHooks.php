<?php

declare(strict_types=1);

namespace Drupal\server_general\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Usage examples.
 */
final class EntityCrudHooks {

  public function __construct() {}

  /**
   * Implements hook_entity_insert().
   */
  #[
    Hook('entity_insert')
  ]
  public function entityInsert(EntityInterface $entity): void {
    switch ($entity->getEntityTypeId()) {
      case 'paragraph_type':
        /** @var \Drupal\paragraphs\ParagraphsTypeInterface $entity */
        $this->entityParagraphTypeInsert($entity);
        break;
    }
  }

  /**
   * Implements hook_entity_insert for paragraph type config entity.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   Paragraph type entity.
   */
  protected function entityParagraphTypeInsert(ParagraphsTypeInterface $paragraph_type) {
    // @todo Add logic to make the paragraph type automatically translatable.
  }

}
