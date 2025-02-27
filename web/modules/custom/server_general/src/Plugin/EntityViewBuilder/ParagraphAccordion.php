<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\AccordionThemeTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * The "Accordion" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.accordion",
 *   label = @Translation("Paragraph - Accordion"),
 *   description = "Paragraph view builder for 'Accordion'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphAccordion extends EntityViewBuilderPluginAbstract {

  use AccordionThemeTrait;
  use ElementLayoutThemeTrait;
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
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $paragraphs */
    $paragraphs = $entity->get('field_accordion_items');
    $items = $this->buildReferencedEntities($paragraphs, 'full', $entity->language()->getId());

    $build[] = $this->buildElementLayoutTitleBodyAndItems(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $this->buildElementAccordion($items),
    );

    return $build;
  }

}
