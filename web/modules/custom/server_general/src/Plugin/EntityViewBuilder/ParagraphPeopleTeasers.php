<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementTrait\CardTrait;
use Drupal\server_general\ElementTrait\PeopleTeasersTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;

/**
 * The "People teasers" paragraph plugin.
 *
 * @EntityViewBuilder (
 *   id = "paragraph.people_teasers",
 *   label = @Translation("Paragraph - People teasers"),
 *   description = "Paragraph view builder for 'People teasers'."
 * )
 *
 * @package Drupal\server_general\Plugin\EntityViewBuilder
 */
class ParagraphPeopleTeasers extends EntityViewBuilderPluginAbstract {

  use CardTrait;
  use ElementWrapTrait;
  use PeopleTeasersTrait;
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
    $paragraphs = $entity->get('field_person_teasers');
    $items = $this->buildReferencedEntities($paragraphs, 'full', $entity->language()->getId());

    $element = $this->buildElementPeopleTeasers(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity, 'field_body'),
      $items,
    );

    $build[] = $element;

    return $build;
  }

}
