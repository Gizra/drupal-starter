<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\AccordionThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * The "Accordion item" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.accordion_item",
 *   label = @Translation("Paragraph - Accordion Item"),
 *   description = "Paragraph view builder for 'Accordion Item'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphAccordionItem extends EntityViewBuilderPluginAbstract {

  use AccordionThemeTrait;

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
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {
    $element = $this->buildElementAccordionItem(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
    );

    $build[] = $element;

    return $build;
  }

}
