<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\EmbedBlockTrait;
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

  use EmbedBlockTrait;
  use SearchThemeTrait;

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
    $build->blockManager = $container->get('plugin.manager.block');
    $build->request = $container->get('request_stack')->getCurrentRequest();
    $build->renderer = $container->get('renderer');

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
    $facets_items = [];
    foreach ($this->facetNames as $facet_name) {
      $facets_items[] = $this->embedBlock('facet_block:' . $facet_name);
    }

    try {
      $search_key = (string) $this->request->query->get('key');
    }
    catch (\Exception $e) {
      // For instance, we have this on malicious input.
      $search_key = '';
    }

    $element = $this->buildElementSearchTermFacetsAndResults(
      $facets_items,
      $this->hasFilters('key'),
      views_embed_view('search', 'embed_1'),
      $search_key,
    );

    $build[] = $element;

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
  protected function hasFilters(string $key = 'search_api_fulltext'): bool {
    // Fix for the "Input value 'f' contains an array" error.
    // Check if key exists in query parameters.
    $key_exists = $this->request->query->has($key) && !empty($this->request->query->get($key));

    // Check if 'f' exists and properly handle it as an array.
    $f_exists = FALSE;
    if ($this->request->query->has('f')) {
      // Get 'f' parameter safely, regardless of whether it's an array or not.
      $f = $this->request->query->all('f');
      $f_exists = !empty($f);
    }

    // Check if page parameter exists.
    $page_exists = $this->request->query->has('page') && $this->request->query->get('page') !== NULL;

    return $key_exists || $f_exists || $page_exists;
  }

}
