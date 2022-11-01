<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\EmbedBlockTrait;
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

  use EmbedBlockTrait;
  use ButtonTrait;
  use ElementWrapTrait;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * The request manager.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $build = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $build->blockManager = $container->get('plugin.manager.block');
    $build->request = $container->get('request_stack')->getCurrentRequest();

    return $build;
  }

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

    // Facets.
    $element = [
      '#theme' => 'server_theme_facets__search',
      '#items' => [
        $this->embedBlock('facet_block:content_type'),
      ],
      '#has_filters' => $this->hasFilters('key'),
    ];
    $build[] = $this->wrapContainerWide($element);

    // Add Main view.
    $element = views_embed_view('search', 'embed_1');
    $build[] = $this->wrapContainerWide($element);

    return $build;
  }

  /**
   * Indicate if facets or keyword(s) are used.
   *
   * This is used in order to know if we should show the "Clear filters" button.
   *
   * @param string $key
   *   The name of the search input field.
   *
   * @return bool
   *   True, if filters are used.
   */
  protected function hasFilters($key = 'search_api_fulltext') : bool {
    return $this->request->query->filter($key) ||
      $this->request->query->filter('f') ||
      $this->request->query->filter('page');
  }

}
