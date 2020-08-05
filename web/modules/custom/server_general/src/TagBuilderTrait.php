<?php

namespace Drupal\server_general;

use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

/**
 * Trait TagBuilderTrait.
 *
 * Helper method for building a tag.
 */
trait TagBuilderTrait {

  /**
   * Get a tag.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to render.
   *
   * @return array
   *   Render array.
   */
  public function buildTag(TermInterface $term) {
    return [
      '#theme' => 'server_theme_tag',
      '#title' => $term->label(),
      // As the style guide is using this with mocked terms (i.e. terms which
      // are not saved), we fallback to a link to the homepage.
      '#url' => !$term->isNew() ? $term->toUrl() : Url::fromRoute('<front>'),
    ];
  }

}
