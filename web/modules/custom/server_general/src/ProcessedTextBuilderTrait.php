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

  /**
   * Build a (processed) text of the content and trim it.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "field_body".
   * @param bool $wrap_prose
   *   Optional; If TRUE then wrap the text with the `prose` classes.
   *   Defaults to FALSE.
   * @param int $trim_length
   *   The trim length. Defaults to 200 chars.
   * @param bool $strip_tags
   *   Determine if output should be stripped of most tags, and keep only a
   *   smaller set of tags. Defaults to TRUE.
   * @param int $line_clamp
   *   If set, `wrapTextLineClamp` will be used on the trimmed text. Defaults to
   *   4.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedTextTrimmed(FieldableEntityInterface $entity, string $field = 'field_body', bool $wrap_prose = FALSE, int $trim_length = 200, bool $strip_tags = TRUE, int $line_clamp = 4) : array {
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

    if ($strip_tags && !empty($element[0]['#text'])) {
      // Keep only a limited set of tags.
      $element[0]['#text'] = strip_tags($element[0]['#text'], [
        'strong',
        'italic',
        'ul',
        'ol',
      ]);
    }

    if ($line_clamp) {
      $element = $this->wrapTextLineClamp($element, 4);
    }

    return $wrap_prose ? $this->wrapProseText($element) : $element;
  }

}
