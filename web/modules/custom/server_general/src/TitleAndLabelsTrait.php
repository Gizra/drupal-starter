<?php

namespace Drupal\server_general;

use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper method for building Title and labels of a content.
 */
trait TitleAndLabelsTrait {

  use BuildFieldTrait;

  /**
   * Build the page title and hide it if it's set to be hidden.
   *
   * The decision whether to hide it or not depends on the value of
   * field_is_title_hidden field on the entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity that's being built.
   *
   * @return array
   *   A renderable array of the page title.
   */
  protected function buildConditionalPageTitle(NodeInterface $entity): array {
    if ($entity->hasField('field_is_title_hidden') && $this->getBooleanFieldValue($entity, 'field_is_title_hidden')) {
      // Title should be hidden.
      return [];
    }

    return [
      '#theme' => 'server_theme_page_title',
      '#title' => $entity->label(),
    ];
  }

  /**
   * Build the labels from text.
   *
   * @param array $labels
   *   The Labels to show.
   *
   * @return array
   *   Render array.
   */
  protected function buildLabelsFromText(array $labels): array {
    // Type labels.
    $items = [];

    foreach ($labels as $label) {
      $items[] = [
        '#theme' => 'server_theme_label',
        '#label' => $label,
      ];
    }

    return [
      '#theme' => 'server_theme_labels',
      '#items' => $items,
    ];
  }

}
