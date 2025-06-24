<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\QuoteThemeTrait;

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

  use ElementWrapThemeTrait;
  use ProcessedTextBuilderTrait;
  use QuoteThemeTrait;

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

    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->getReferencedEntityFromField($entity, 'field_image');
    $image_credit = $this->getTextFieldValue($media, 'field_media_credit');

    $element = $this->buildElementQuote(
      $this->buildMediaResponsiveImage($entity, 'field_image', 'quote'),
      $this->buildProcessedText($entity, 'field_body'),
      $this->getTextFieldValue($entity, 'field_subtitle'),
      $image_credit,
    );

    $build[] = $element;

    return $build;
  }

}
