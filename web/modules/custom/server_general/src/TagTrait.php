<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\server_general\ThemeTrait\TagThemeTrait;

/**
 * Trait TagBuilderTrait.
 *
 * Helper method for building a tag out of a content.
 */
trait TagTrait {

  use TagThemeTrait;

  /**
   * Build the tags element out of a field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The referencing entity.
   * @param string $field_name
   *   The field name. Defaults to `field_tags`.
   *
   * @return array
   *   Render array.
   */
  protected function buildTags(FieldableEntityInterface $entity, string $field_name = 'field_tags'): array {
    if (empty($entity->{$field_name}) || $entity->{$field_name}->isEmpty()) {
      return [];
    }

    $items = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($entity->{$field_name}->referencedEntities() as $term) {
      $element = $this->buildTag($term->label(), $term->toUrl());

      $cache = CacheableMetadata::createFromRenderArray($element);
      $cache->addCacheableDependency($term);

      $items[] = $element;
    }

    $title = $entity->{$field_name}->getFieldDefinition()->getLabel();

    return $this->buildElementTags($title, $items);
  }

}
