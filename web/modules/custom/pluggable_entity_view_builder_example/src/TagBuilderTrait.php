<?php

namespace Drupal\pluggable_entity_view_builder_example;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Trait TagBuilderTrait.
 *
 * Helper method for building a tag.
 */
trait TagBuilderTrait {

  /**
   * Build a single tag.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The Term to render.
   *
   * @return array
   *   Render array.
   */
  protected function buildTag(TermInterface $term): array {
    $element = [
      '#theme' => 'pluggable_entity_view_builder_example_tag',
      '#title' => $term->label(),
      // As the style guide is using this with mocked terms (i.e. terms which
      // are not saved), we fallback to a link to the homepage.
      '#url' => !$term->isNew() ? $term->toUrl() : Url::fromRoute('<front>'),
      '#border' => TRUE,
    ];

    $cache = CacheableMetadata::createFromObject($element);
    $cache->addCacheableDependency($term);
    $cache->applyTo($element);

    return $element;
  }

}
