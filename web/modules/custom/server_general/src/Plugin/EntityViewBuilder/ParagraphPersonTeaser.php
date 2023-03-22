<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;

/**
 * The "Person teaser" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.person_teaser",
 *   label = @Translation("Paragraph - Person teaser"),
 *   description = "Paragraph view builder for 'Person teaser'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphPersonTeaser extends EntityViewBuilderPluginAbstract {

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
    $image = $this->getMediaImageAndAlt($entity, 'field_media_image');

    $element = $this->buildCardPersonTeaser(
      $image['url'],
      $this->getTextFieldValue($entity, 'field_title'),
      $this->getTextFieldValue($entity, 'field_subtitle'),
    );

    $build[] = $element;

    return $build;
  }

}
