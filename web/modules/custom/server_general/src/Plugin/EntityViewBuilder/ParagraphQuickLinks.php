<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\CardThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\QuickLinksThemeTrait;

/**
 * The "Quick links" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.quick_links",
 *   label = @Translation("Paragraph - Quick links"),
 *   description = "Paragraph view builder for 'Quick links'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphQuickLinks extends EntityViewBuilderPluginAbstract {

  use CardThemeTrait;
  use ElementWrapThemeTrait;
  use ProcessedTextBuilderTrait;
  use QuickLinksThemeTrait;

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
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs */
    $paragraphs = $entity->get('field_quick_link_items');
    $items = $this->buildReferencedEntities($paragraphs, 'full', $entity->language()->getId());

    if (empty($items)) {
      // While building the quick link items, we checked and got that user has
      // no access to any item.
      return $build;
    }

    $element = $this->buildElementQuickLinks(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $items,
    );

    $build[] = $element;

    return $build;
  }

}
