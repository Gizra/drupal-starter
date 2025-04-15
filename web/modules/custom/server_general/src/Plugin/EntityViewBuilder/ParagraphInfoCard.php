<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\InfoCardThemeTrait;

/**
 * The "Info card" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.info_card",
 *   label = @Translation("Paragraph - Info card"),
 *   description = "Paragraph view builder for 'Info card'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphInfoCard extends EntityViewBuilderPluginAbstract {


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
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {
    $element = $this->buildElementInfoCard(
      $this->getTextFieldValue($entity, 'field_info_card_header'),
      $this->getTextFieldValue($entity, 'field_title'),
      $this->getTextFieldValue($entity, 'field_subtitle'),
    );

    $build[] = $element;

    return $build;
  }

}
