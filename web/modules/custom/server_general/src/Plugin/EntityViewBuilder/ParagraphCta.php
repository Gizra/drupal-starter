<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Link;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ButtonThemeTrait;
use Drupal\server_general\ThemeTrait\CtaThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * The "Call to Action" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.cta",
 *   label = @Translation("Paragraph - CTA"),
 *   description = "Paragraph view builder for 'Call to Action' bundle."
 * )
 */
class ParagraphCta extends EntityViewBuilderPluginAbstract {

  use ButtonThemeTrait;
  use CtaThemeTrait;
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
    $link = $this->getLinkFieldValue($entity, 'field_link');
    if (empty($link)) {
      return [];
    }

    $element = $this->buildElementCta(
      $this->getTextFieldValue($entity, 'field_title'),
      $this->buildProcessedText($entity),
      Link::fromTextAndUrl($link['title'], $link['url']),
    );

    $build[] = $element;

    return $build;
  }

}
