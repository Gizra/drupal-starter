<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;

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

    $build[] = [
      '#theme' => 'server_theme_related_content',
      '#title' => $this->getTextFieldValue($entity, 'field_title'),
      '#items' => $this->buildReferencedEntities($related_content, 'teaser'),
      '#button' => $this->buildLinkButton($entity),
    ];

    return $build;
  }

}
