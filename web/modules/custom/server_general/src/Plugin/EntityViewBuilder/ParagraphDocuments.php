<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ButtonThemeTrait;
use Drupal\server_general\ThemeTrait\DocumentsThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * The "Documents" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.documents",
 *   label = @Translation("Paragraph - Document"),
 *   description = "Paragraph view builder for 'Documents' bundle."
 * )
 */
class ParagraphDocuments extends EntityViewBuilderPluginAbstract {

  use ButtonThemeTrait;
  use DocumentsThemeTrait;
  use ElementWrapThemeTrait;
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
    // Documents.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $items = $entity->get('field_documents');
    $items = $this->buildReferencedEntities($items, 'card', $entity->language()->getId());

    $element = $this->buildElementDocuments(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $items,
    );

    $build[] = $element;

    return $build;
  }

}
