<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Link;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\ButtonThemeTrait;
use Drupal\server_general\ThemeTrait\HeroThemeTrait;

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

  use BuildFieldTrait;
  use ButtonThemeTrait;
  use HeroThemeTrait;

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
    $value = $this->getLinkFieldValue($entity, 'field_link');

    $element = $this->buildElementHeroImage(
      $this->buildMediaResponsiveImage($entity, 'field_image', self::RESPONSIVE_IMAGE_STYLE_ID),
      $this->getTextFieldValue($entity, 'field_title'),
      $this->getTextFieldValue($entity, 'field_subtitle'),
      $value ? Link::fromTextAndUrl($value['title'], $value['url']) : NULL,
    );
    $build[] = $element;

    return $build;
  }

}
