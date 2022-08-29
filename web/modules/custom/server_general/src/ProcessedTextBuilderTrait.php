<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Trait ProcessedTextBuilderTrait.
 *
 * Helper method for building a Processed text (e.g. a body field).
 */
trait ProcessedTextBuilderTrait {

  /**
   * Build a (processed) text of the content.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
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
  protected function buildProcessedText(FieldableEntityInterface $entity, string $field = 'field_body', bool $summary_or_trimmed = FALSE) : array {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      // Field is empty or doesn't exist.
      return [];
    }

    // Hide the label.
    $options = ['label' => 'hidden'];

    if ($summary_or_trimmed) {
      $options['type'] = 'text_summary_or_trimmed';
    }

    return [
      '#theme' => 'server_theme_content__body',
      '#content' => $entity->get($field)->view($options),
    ];
  }

}
