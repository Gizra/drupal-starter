<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\InfoCardThemeTrait;

/**
 * The "Info cards" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.info_cards",
 *   label = @Translation("Paragraph - Info cards"),
 *   description = "Paragraph view builder for 'Info cards'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphInfoCards extends EntityViewBuilderPluginAbstract {

  use ElementWrapThemeTrait;
  use InfoCardThemeTrait;
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
    $paragraphs = $entity->get('field_info_cards');
    $items = $this->buildReferencedEntities($paragraphs, 'full', $entity->language()->getId());

    $element = $this->buildElementInfoCards(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $items,
    );

    $build[] = $element;

    return $build;
  }

}
