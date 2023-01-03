<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ElementTrait;

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
  use ButtonTrait;
  use ElementTrait;

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
      $value['title'],
      $value['url'],
    );
    $build[] = $element;

    return $build;
  }

}
