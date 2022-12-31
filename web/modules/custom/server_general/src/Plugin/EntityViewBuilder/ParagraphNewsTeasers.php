<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\views\Views;

/**
 * The "News teasers" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.news_teasers",
 *   label = @Translation("Paragraph - News teasers"),
 *   description = "Paragraph view builder for 'News teasers' bundle."
 * )
 */
class ParagraphNewsTeasers extends EntityViewBuilderPluginAbstract {

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
    $view = Views::getView('news');
    if (empty($view)) {
      return [];
    }

    $view->preview('embed_1');
    $view->execute();
    if (empty($view->result)) {
      // No results. Do not render.
      return [];
    }

    $element = $this->getTextFieldValue($entity, 'field_title');
    $element = $this->wrapHtmlTag($element, 'h2');
    $element = $this->wrapTextFontWeight($element, 'bold');
    $elements[] = $element;

    $elements[] = $view->buildRenderable('embed_1');

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    $elements = $this->wrapContainerWide($elements);

    $build[] = $elements;
    return $build;
  }

}
