<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\CarouselThemeTrait;

/**
 * The "Related content" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.related_content",
 *   label = @Translation("Paragraph - Related content"),
 *   description = "Paragraph view builder for 'Related content' bundle."
 * )
 */
class ParagraphRelatedContent extends EntityViewBuilderPluginAbstract {

  use ButtonTrait;
  use CarouselThemeTrait;
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
    if ($entity->get('field_related_content')->isEmpty()) {
      return $build;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $related_content */
    $related_content = $entity->get('field_related_content');
    $is_featured = $this->getBooleanFieldValue($entity, 'field_is_featured');
    $view_mode = $is_featured ? 'featured' : 'teaser';

    $cache_metadata = CacheableMetadata::createFromRenderArray($build);
    $element = $this->buildElementCarousel(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $this->buildReferencedEntities($cache_metadata, $related_content, $view_mode),
      $is_featured,
      $this->buildLinkButton($entity),
      TRUE,
    );
    $build[] = $element;
    $cache_metadata->applyTo($build);

    return $build;
  }

}
