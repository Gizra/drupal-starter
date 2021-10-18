<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;

/**
 * The "Views" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.views",
 *   label = @Translation("Paragraph - Views"),
 *   description = "Paragraph view builder for 'Views' bundle."
 * )
 */
class ParagraphViews extends EntityViewBuilderPluginAbstract {

  use ElementWrapTrait;

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
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $views_field */
    $views_field = $entity->get('field_views');
    // Views.
    $element = $views_field->view(['label' => 'hidden']);
    $build[] = $this->wrapElementWideContainer($element);

    return $build;
  }

}
