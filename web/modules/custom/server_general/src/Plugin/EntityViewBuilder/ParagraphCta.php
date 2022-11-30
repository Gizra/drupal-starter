<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ElementWrapTrait;

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

  use ButtonTrait;
  use ElementWrapTrait;

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
    $element = [
      '#theme' => 'server_theme_paragraph__cta',
      '#title' => $this->getTextFieldValue($entity, 'field_title'),
      '#subtitle' => $this->getTextFieldValue($entity, 'field_subtitle'),
      '#button' => $this->buildLinkButton($entity),
    ];
    $build[] = $element;

    return $build;
  }

  /**
   * Get link info.
   *
   * @param mixed $entity
   *   The entity.
   * @param string $field_name
   *   The machine name of the field holding the link.
   *
   * @return array
   *   Render array.
   */
  protected function getLink($entity, string $field_name = 'field_link') {
    $link = NULL;
    if (!$entity->get($field_name)->isEmpty()) {
      $link = $entity->$field_name->getValue();
      $link = reset($link);
    }
    return $link;
  }

}
