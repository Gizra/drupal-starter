<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;

/**
 * The "Text" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.text",
 *   label = @Translation("Paragraph - Text"),
 *   description = "Paragraph view builder for 'Text' bundle."
 * )
 */
class ParagraphText extends EntityViewBuilderPluginAbstract {

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {
    $element[] = [
      '#theme' => 'server_theme_text',
      '#title' => $this->getTextFieldValue($entity, 'field_title'),
      '#body' => $this->buildProcessedText($entity, 'field_body'),
    ];

    $build[] = $this->wrapElementWideContainer($element);

    return $build;
  }

}
