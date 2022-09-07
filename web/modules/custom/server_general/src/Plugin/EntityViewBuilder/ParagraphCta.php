<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
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
    $link = $this->getLink($entity);

    $build[] = [
      '#theme' => 'server_theme_cta',
      '#title' => $this->getTextFieldValue($entity, 'field_title'),
      '#subtitle' => $this->getTextFieldValue($entity, 'field_subtitle'),
      '#url' => Url::fromUri($link['uri']),
      '#url_title' => $link['title'],
    ];

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
