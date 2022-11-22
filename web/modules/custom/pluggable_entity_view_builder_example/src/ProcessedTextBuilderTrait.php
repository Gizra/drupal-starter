<?php

namespace Drupal\pluggable_entity_view_builder_example;

use Drupal\Core\Entity\EntityInterface;

/**
 * Trait ProcessedTextBuilderTrait.
 *
 * Helper method for building a Processed text (e.g. a body field).
 */
trait ProcessedTextBuilderTrait {

  /**
   * Build the comments and comment from of a node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  protected function buildComment(EntityInterface $entity): array {
    if (empty($entity->comment)) {
      // Comment field doesn't exist.
      return [];
    }

    return $entity->comment->view();
  }

  /**
   * Build a (processed) text of the content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "body".
   * @param bool $summary_or_trimmed
   *   Optional; If TRUE then the "summary or trimmed" formatter will be used.
   *   Defaults to FALSE.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedText(EntityInterface $entity, string $field = 'body', bool $summary_or_trimmed = FALSE): array {
    if (empty($entity->{$field}) || $entity->{$field}->isEmpty()) {
      // Field doesn't exist, or empty.
      return [];
    }

    $options = ['label' => 'hidden'];

    if ($summary_or_trimmed) {
      $options['type'] = 'text_summary_or_trimmed';
    }

    return [
      '#theme' => 'pluggable_entity_view_builder_example_body',
      '#content' => $entity->get($field)->view($options),
    ];
  }

}
