<?php

namespace Drupal\server_general;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Url;

/**
 * Trait TagBuilderTrait.
 *
 * Helper method for building a tag.
 */
trait TagTrait {

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

  /**
   * Build a tag.
   *
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The Url object.
   *
   * @return array
   *   Render array.
   */
  protected function buildTag(string $title, Url $url): array {
    return [
      '#theme' => 'server_theme_tag',
      '#title' => $title,
      '#url' => $url,
    ];
  }

  /**
   * Build the Tags element.
   *
   * @param string $title
   *   The title.
   * @param array $items
   *   The render array built with `::buildTag`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementTags(string $title, array $items): array {
    return [
      '#theme' => 'server_theme_tags',
      '#title' => $title,
      '#items' => $items,
    ];
  }

}
