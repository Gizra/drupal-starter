<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;

/**
 * The "Hero image" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.hero_image",
 *   label = @Translation("Paragraph - Hero image"),
 *   description = "Paragraph view builder for 'Hero image' bundle."
 * )
 */
class ParagraphHeroImage extends EntityViewBuilderPluginAbstract {

  use ButtonTrait;

  const RESPONSIVE_IMAGE_STYLE_ID = 'hero';

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
    $build[] = [
      '#theme' => 'server_theme_hero_image',
      '#image' => $this->buildMediaResponsiveImage($entity, 'field_image', self::RESPONSIVE_IMAGE_STYLE_ID),
      '#title' => $this->getTextFieldValue($entity, 'field_title'),
      '#subtitle' => $this->getTextFieldValue($entity, 'field_subtitle'),
      '#button' => $this->buildLinkButton($entity),
    ];

    return $build;
  }

}
