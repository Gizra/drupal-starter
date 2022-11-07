<?php

declare(strict_types=1);

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
   * The machine name of the facets to show.
   *
   * When adding or editing facets for example
   * `/admin/config/search/facets/content_type/edit` make sure to uncheck
   * "Hide facet when facet source is not rendered", otherwise it will not
   * appear.
   * You will also likely want to check the "List item label" option, for the
   * label to appear instead of the key.
   *
   * @var array
   */
  protected array $facetNames = [
    'content_type',
  ];

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
    $elements = [];

    // Search term and facets.
    $elements[] = $this->buildSearchTermAndFacets();

    // Add Main view.
    $element = views_embed_view('search', 'embed_1');
    $elements[] = $this->wrapContainerWide($element);

    $element = $this->wrapContainerVerticalSpacingBig($elements);
    $build[] = $this->wrapContainerBottomPadding($element);

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
  protected function hasFilters($key = 'search_api_fulltext'): bool {
    return $this->request->query->filter($key) ||
      $this->request->query->filter('f') ||
      $this->request->query->filter('page');
  }

  /**
   * Build the Search term and facets.
   *
   * @return array
   *   Render array
   */
  protected function buildSearchTermAndFacets(): array {
    $elements = [];
    $search_term = $this->request->query->get('key');
    if ($search_term) {
      $element = [
        '#theme' => 'server_theme_search_term',
        '#search_term' => $search_term,
      ];
      $elements[] = $this->wrapContainerWide($element);
    }

    // Facets.
    $items = [];
    foreach ($this->facetNames as $facet_name) {
      $items[] = $this->embedBlock('facet_block:' . $facet_name);
    }

    $element = [
      '#theme' => 'server_theme_facets__search',
      '#items' => $items,
      '#has_filters' => $this->hasFilters('key'),
    ];
    $elements[] = $this->wrapContainerWide($element);

    return $this->wrapContainerVerticalSpacing($elements);
  }

}
