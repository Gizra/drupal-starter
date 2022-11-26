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
   *
   * @return array
   *   Render array.
   */
  public function buildCaption(MediaInterface $entity, string $field_name = 'field_caption'): array {
    $caption = $this->getTextFieldValue($entity, $field_name);
    if (empty($caption)) {
      return [];
    }

    return [
      '#theme' => 'server_theme_media_caption',
      '#caption' => $caption,
    ];
  }

}
