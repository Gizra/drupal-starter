<?php

declare(strict_types=1);

namespace Drupal\og_entity_view_builder\ThemeTrait;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Helper method for building a content Title.
 */
trait TextThemeTrait {

  /**
   * Build the page title element.
   *
   * @param string $title
   *   The title.
   *
   * @return array
   *   The render array.
   */
  protected function buildPageTitle(string $title): array {
    return [
      '#theme' => 'server_theme_page_title',
      '#title' => $title,
    ];
  }

  /**
   * Build a section of (processed) text from the content.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   (optional) The name of the field. Defaults to "field_body".
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedText(FieldableEntityInterface $entity, string $field = 'field_body') : array {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      // Field is empty or doesn't exist.
      return [];
    }

    // Hide the label.
    $options = ['label' => 'hidden'];

    return $entity->get($field)->view($options);
  }

}
