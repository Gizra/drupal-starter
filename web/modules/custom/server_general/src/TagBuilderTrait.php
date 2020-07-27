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
   *   The renderable array.
   */
  public function buildTag(TermInterface $term) {
    $classes = 'mr-1 text-ms px-3 py-1 my-1 text-center leading-normal rounded-large border-2 border-purple-primary hover:text-blue-900 hover:border-blue-900 rounded-md text-purple-primary h-8 overflow-hidden';

    return [
      '#type' => 'link',
      '#title' => $term->label(),
      // As the style guide is using this with mocked terms (i.e. terms which
      // are not saved), we fallback to a link to the homepage.
      '#url' => !$term->isNew() ? $term->toUrl() : Url::fromRoute('<front>'),
      '#attributes' => ['class' => explode(' ', $classes)],
    ];
  }
}
