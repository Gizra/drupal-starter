<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\media\MediaInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper method for building caption for a Media entity.
 */
trait MediaCaptionTrait {

  use BuildFieldTrait;

  /**
   * Build a list of tags out of a field.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name. Defaults to `field_caption`.
   * @param string $credit_field_name
   *   The credit field name. Default: 'field_photo_credit'.
   * @param string $caption_alignment
   *   The caption alignment. Default: 'start'.
   *
   * @return array
   *   Render array.
   */
  public function buildCaption(MediaInterface $entity, string $field_name = 'field_caption', string $credit_field_name = 'field_photo_credit', string $caption_alignment = 'start'): array {
    $elements = [];

    if ($entity->hasField($field_name)) {
      $element[] = $this->wrapTextResponsiveFontSize($this->getTextFieldValue($entity, $field_name), 'base');
    }
    if ($entity->hasField($credit_field_name)) {
      $element = $this->wrapTextResponsiveFontSize($this->getTextFieldValue($entity, $credit_field_name), 'base');
      $elements[] = $this->wrapTextItalic($element);
    }
    if (empty($elements)) {
      // Nothing to output.
      return [];
    }
    return $this->wrapContainerVerticalSpacingTiny($elements, $caption_alignment);

  }

}
