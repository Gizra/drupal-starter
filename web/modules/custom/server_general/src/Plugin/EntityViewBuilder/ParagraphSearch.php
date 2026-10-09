<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Render\RendererInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ThemeTrait\SearchThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The "Search" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.search",
 *   label = @Translation("Paragraph - Search"),
 *   description = "Paragraph view builder for 'Search' bundle."
 * )
 */
class ParagraphSearch extends EntityViewBuilderPluginAbstract {

  use SearchThemeTrait;

  /**
   * The request manager.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $build = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $build->request = $container->get('request_stack')->getCurrentRequest();
    $build->renderer = $container->get('renderer');

    return $build;
  }

  /**
   * Build full view mode.
   *
   * The facets are exposed filters of the "search" view, so the view renders
   * them itself.
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
    try {
      $search_key = (string) $this->request->query->get('key');
    }
    catch (\Exception $e) {
      // For instance, we have this on malicious input.
      $search_key = '';
    }

    $build[] = $this->buildElementSearchTermAndResults(
      views_embed_view('search', 'embed_1'),
      $search_key,
    );

    return $build;
  }

}
