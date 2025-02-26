<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementWrapTrait;
use Drupal\server_general\ThemeTrait\QuickLinksTrait;

/**
 * The "Quick link item" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.quick_link_item",
 *   label = @Translation("Paragraph - Quick link item"),
 *   description = "Paragraph view builder for 'Quick link item'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphQuickLinkItem extends EntityViewBuilderPluginAbstract {


  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;
  use QuickLinksTrait;

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
    $link = $this->getLinkFieldValue($entity, 'field_link');
    if (empty($link)) {
      // We have no access to the link.
      return [];
    }

    $element = $this->buildElementQuickLinkItem(
      $link['title'],
      $link['url'],
      $this->getTextFieldValue($entity, 'field_subtitle')
    );

    $build[] = $element;

    return $build;
  }

}
