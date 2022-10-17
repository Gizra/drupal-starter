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
   * @param bool $wrap_prose
   *   Optional; If TRUE then wrap the text with the `prose` classes.
   *   Defaults to TRUE.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedText(FieldableEntityInterface $entity, string $field = 'field_body', bool $wrap_prose = TRUE) : array {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      // Field is empty or doesn't exist.
      return [];
    }

    // Hide the label.
    $options = ['label' => 'hidden'];

    $element = $entity->get($field)->view($options);
    return $wrap_prose ? $this->wrapProseText($element) : $element;
  }

}
