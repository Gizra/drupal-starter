<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\pluggable_entity_view_builder\ComponentWrapTrait;
use Drupal\server_general\ButtonBuilderTrait;
use Drupal\server_general\TagBuilderTrait;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use ButtonBuilderTrait;
  use ComponentWrapTrait;
  use TagBuilderTrait;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  private $blockManager;


  /**
   * The renderer service.
   *
   * @var \Drupal\server_style_guide\Controller\Renderer
   */
  protected $renderer;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Class constructor.
   */
  public function __construct(BlockManager $block_manager, Renderer $renderer, LinkGenerator $link_generator) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('link_generator')
    );
  }

  /**
   * Returns the "Style Guide" page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function styleGuidePage() {
    $card_image = $this->getPlaceholderImage(600, 520);

    $tags = [
      $this->getMockedTag('The transporter'),
      $this->getMockedTag('Is more girl'),
    ];

    $many_tags = $tags + [
      $this->getMockedTag('The flight'),
      $this->getMockedTag('bare klingon'),
      $this->getMockedTag('Dogma doesn’t balanced understand'),
      $this->getMockedTag('The plank hails with courage'),
      $this->getMockedTag('burn the freighter until it rises'),
    ];

    $single_card_simple = [
      '#theme' => 'server_theme_card__simple',
      '#image' => $card_image,
      '#title' => 'The source has extend, but not everyone fears it.',
      '#body' => 'Decorate one package of cauliflower in six teaspoons of plain vinegar. Try flavoring the crême fraîche gingers with clammy rum and fish sauce, simmered.',
      '#tags' => $tags,
    ];

    $single_card_no_body = $single_card_simple;
    unset($single_card_no_body['#body']);

    $single_card_long_title = $single_card_simple;
    $single_card_long_title['#title'] = 'How Professional Learning Networks Are Helping Educators Get Through Coronavirus';
    $single_card_long_title['#tags'] = $many_tags;

    $single_card_long_author_name = $single_card_simple;
    $single_card_long_author_name['#author'] = 'Someone with A. Very long name';

    $cards = [
      $single_card_simple,
      $single_card_no_body,
      $single_card_long_title,
      $single_card_long_author_name,
    ];

    $rows = [];
    foreach ($cards as $card) {
      $rows[] = [
        'content' => $card,
        'attributes' => [],
      ];
    }

    $element['server_theme_cards'] = [
      '#prefix' => $this->getComponentPrefix('Multiple Cards - With Title'),
      '#theme' => 'server_theme_cards',
      '#title' => $this->t('Related Items'),
      '#rows' => $rows,
    ];

    // Buttons.
    $element['server_theme_button'] = $this->buildButton(
      $this->t('Register'),
      '#',
      'turquoise'
    );
    $element['server_theme_button']['#prefix'] = $this->getComponentPrefix('Button - no Icon');

    $element['server_theme_button__icon_calendar'] = $this->buildButton(
      $this->t('Add to my calendar'),
      '#',
      'purple-primary',
      'calendar'
    );
    $element['server_theme_button__icon_calendar']['#prefix'] = $this->getComponentPrefix('Button - with Icon');

    $element['server_theme_button__print'] = $this->buildButton(
      $this->t('Print'),
      'javascript:void(0)',
      'purple-primary',
      'print',
      'window.print()'
    );
    $element['server_theme_button__print']['#prefix'] = $this->getComponentPrefix('Button - Print (OnClick)');

    $element['server_theme_content__tags'] = [
      '#prefix' => $this->getComponentPrefix('Content Tags'),
      '#theme' => 'server_theme_content__tags',
      '#tags' => $many_tags,
    ];

    $element['server_theme_content__image_and_teaser'] = [
      '#prefix' => $this->getComponentPrefix('Content Image and Teaser'),
      '#theme' => 'server_theme_content__image_and_teaser',
      '#image' => $this->getPlaceholderImage(940, 265),
      '#teaser' => [
        '#type' => 'processed_text',
        '#text' => 'Diatrias favere! Sunt tataes <strong>visum superbus</strong>, clemens mineralises. Who can need the acceptance and afterlife of a doer if he has the abstruse issue of the self?',
        '#format' => filter_default_format(),
      ],
    ];

    $table_header = [
      $this->t('Company Name'),
      $this->t('Number of Employees'),
      $this->t('Industry'),
      $this->t('Address'),
      $this->t('Main Contact'),
    ];

    $table_rows = [];

    // 1st row.
    $row = [
      'data' => [],
      'class' => [],
    ];

    $row['data'][] = 'AK Steel';
    $row['data'][] = '289 employees';
    $row['data'][] = 'Manufacturing';
    $row['data'][] = "Urban Place - Coworking Space, 9 Ahad Ha'Am St Tel Aviv";
    $row['data'][] = $this->linkGenerator->generate('Wilson Lukus', Url::fromUri('https://example.com'));

    $table_rows[] = $row;

    // 2nd row.
    $row = [
      'data' => [],
      'class' => [],
    ];

    $row['data'][] = 'Ruck Candy Company';
    $row['data'][] = '750000 employees';
    $row['data'][] = 'Health';
    $row['data'][] = "Some really really long address here, it's somewhere in the world, but we don't know exactly where";
    $row['data'][] = $this->linkGenerator->generate('Caroline Wagner Lukus', Url::fromUri('https://example.com'));

    $table_rows[] = $row;

    $element['table'] = [
      '#prefix' => $this->getComponentPrefix('Table (Responsive)', 'https://tailwindcomponents.com/component/responsive-table'),
      '#type' => 'table',
      '#header' => $table_header,
      '#rows' => $table_rows,
    ];

    $element['server_theme_footer'] = [
      '#prefix' => $this->getComponentPrefix('Footer'),
      '#theme' => 'server_theme_footer',
    ];

    $element['server_theme_user_image__photo'] = [
      '#prefix' => $this->getComponentPrefix('User Image - With Photo'),
      '#theme' => 'server_theme_user_image',
      '#image' => $this->getPlaceholderPersonImage(256, 256),
      '#image_alt' => 'Bill Murray',
      '#url' => '#',
    ];

    $element['server_theme_user_image__initials'] = [
      '#prefix' => $this->getComponentPrefix('User Image - No Photo'),
      '#theme' => 'server_theme_user_image',
      '#initials' => 'BM',
      '#url' => '#',
    ];

    // Add container around each element.
    $build = [];
    foreach ($element as $value) {
      $build[] = $this->wrapComponentWithContainer($value, '', 'fluid-container-wide');
    }

    return $build;
  }

  /**
   * Get the prefix to how as the component's title.
   *
   * @param string $title
   *   The component name.
   * @param string $link
   *   Optional; Link to the design.
   *
   * @return string
   *   The Html for the prefix.
   *
   * @throws \Exception
   */
  protected function getComponentPrefix($title, $link = NULL) {
    $id = Html::getUniqueId($title);

    $build = [
      '#theme' => 'server_style_guide_header',
      '#title' => $title,
      '#unique_id' => $id,
      '#link' => $link,
    ];

    return $this->renderer->render($build);
  }

  /**
   * Get image placeholder.
   *
   * @param int $width
   *   The width of the image.
   * @param int $height
   *   The height of the image.
   *
   * @return string
   *   URL with placeholder.
   */
  protected function getPlaceholderImage(int $width, int $height) {
    return "https://via.placeholder.com/{$width}x{$height}.png";
  }

  /**
   * Get placeholder image of a person.
   *
   * @param int $width
   *   The width of the image.
   * @param int $height
   *   The height of the image.
   *
   * @return string
   *   URL with placeholder.
   */
  protected function getPlaceholderPersonImage(int $width, int $height) {
    return "https://www.fillmurray.com/{$width}/{$height}";
  }

  /**
   * Get a tag.
   *
   * @param string $title
   *   The title of the tag.
   *
   * @return array
   *   The renderable array.
   */
  public function getMockedTag($title) {
    $dummy_term = Term::create([
      'vid' => 'example_vocabulary_machine_name',
      'name' => $title,
    ]);

    return $this->buildTag($dummy_term);
  }

}
