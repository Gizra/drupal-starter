<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;

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

  use ProcessedTextBuilderTrait;
  use ElementLayoutThemeTrait;

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
    $element = $this->buildElementLayoutTitleAndContent(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
    );

    $build[] = $element;
    return $build;
  }

}
