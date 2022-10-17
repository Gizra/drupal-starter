<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\FieldableEntityInterface;
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

  /**
   * Build a list of tags out of a field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The referencing entity.
   * @param string $field_name
   *   The field name. Defaults to `field_tags`.
   *
   * @return array
   *   Render array.
   */
  public function buildTags(FieldableEntityInterface $entity, string $field_name = 'field_tags'): array {
    if (empty($entity->{$field_name}) || $entity->{$field_name}->isEmpty()) {
      return [];
    }

    $items = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($entity->{$field_name}->referencedEntities() as $term) {
      $items[] = $this->buildTag($term);
    }

    $title = $entity->{$field_name}->getFieldDefinition()->getLabel();

    return [
      '#theme' => 'server_theme_tags',
      '#title' => $title,
      '#items' => $items,
    ];
  }

}
