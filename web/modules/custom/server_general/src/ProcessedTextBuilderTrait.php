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
   *   Optional; The name of the field. Defaults to "field_body".
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

  /**
   * Build a (processed) text of the content and trim it.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "field_body".
   * @param int $trim_length
   *   The trim length. Defaults to 200 chars.
   * @param bool $strip_tags
   *   Determine if output should be stripped of most tags, and keep only a
   *   smaller set of tags. Defaults to TRUE.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedTextTrimmed(FieldableEntityInterface $entity, string $field = 'field_body', int $trim_length = 200, bool $strip_tags = TRUE) : array {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      // Field is empty or doesn't exist.
      return [];
    }

    // Hide the label.
    $options = [
      'label' => 'hidden',
      'type' => 'smart_trim',
      'settings' => [
        'trim_length' => $trim_length,
        'trim_suffix' => '…',
      ],
    ];

    $element = $entity->get($field)->view($options);

    if ($strip_tags && !empty($element[0]['#output']['#text'])) {
      // Keep only a limited set of tags.
      $element[0]['#output']['#text'] = strip_tags($element[0]['#output']['#text'], [
        'strong',
        'em',
        'ul',
        'ol',
      ]);
    }

    return $element;
  }

}
