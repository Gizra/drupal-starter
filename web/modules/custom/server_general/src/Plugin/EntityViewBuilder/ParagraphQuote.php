<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;

/**
 * The "Quote" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.quote",
 *   label = @Translation("Paragraph - Quote"),
 *   description = "Paragraph view builder for 'Quote'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphQuote extends EntityViewBuilderPluginAbstract {

  use ElementTrait;
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
    $element = $this->buildElementQuote(
      $this->buildProcessedText($entity, 'field_body', FALSE),
      $this->getTextFieldValue($entity, 'field_subtitle'),
    );

    $build[] = $element;

    return $build;
  }

}
