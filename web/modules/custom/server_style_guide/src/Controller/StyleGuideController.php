<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\intl_date\IntlDate;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\TagBuilderTrait;
use Drupal\server_general\TitleAndLabelsTrait;
use Drupal\server_style_guide\ElementWrapTrait;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use BuildFieldTrait;
  use ButtonTrait;
  use ElementWrapTrait;
  use TagBuilderTrait;
  use TitleAndLabelsTrait;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Class constructor.
   */
  public function __construct(LinkGenerator $link_generator) {
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
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
    $build = [];

    // Wide container.
    $elements = $this->getWideWidthElements();

    // No container.
    $elements = array_merge($elements, $this->getFullWidthElements());

    $build[] = [
      '#theme' => 'server_style_guide_wrapper',
      '#elements' => $elements,
    ];

    $build['#attached']['library'][] = 'server_style_guide/accordion';

    return $build;
  }

  /**
   * Define all elements here that should be 'wide' width.
   *
   * @return array
   *   A render array containing the elements.
   */
  protected function getWideWidthElements() : array {
    $build = [];

    $element = $this->getPageTitle();
    $build[] = $this->wrapElementWideContainer($element, 'Page title');

    $build[] = $this->getButtons();

    $element = $this->getCards();
    $build[] = $this->wrapElementWideContainer($element, 'Cards');

    $element = $this->getTags();
    $build[] = $this->wrapElementWideContainer($element, 'Tags');

    $element = $this->getSearchResult();
    $build[] = $this->wrapElementWideContainer($element, 'Search result');

    return $build;
  }

  /**
   * Define all elements here that should be 'full' width.
   *
   * Elements spanning full-width of the document.
   *
   * @return array
   *   A render array containing the elements.
   */
  protected function getFullWidthElements(): array {
    $build = [];

    $element = $this->getHeroImage();
    $build[] = $this->wrapElementNoContainer($element, 'Hero image');

    $element = $this->getRelatedContentCarousel();
    $build[] = $this->wrapElementNoContainer($element, 'Related content');

    $element = $this->getFooter();
    $build[] = $this->wrapElementNoContainer($element, 'Footer');

    $element = $this->getCta();
    $build[] = $this->wrapElementNoContainer($element, 'Call to Action');

    return $build;
  }

  /**
   * Get the page title.
   *
   * @return array
   *   Render array.
   */
  protected function getPageTitle(): array {
    return [
      '#theme' => 'server_theme_page_title',
      '#title' => 'The source has extend, but not everyone fears it',
    ];
  }

  /**
   * Get tags.
   *
   * @return array
   *   Render array.
   */
  protected function getTags(): array {
    $items = [
      $this->buildMockedTag('The transporter'),
      $this->buildMockedTag('Is more girl'),
    ];

    return [
      '#theme' => 'server_theme_tags',
      '#title' => 'Tags',
      '#items' => $items,
    ];
  }

  /**
   * Get A single Search result.
   *
   * @return array
   *   Render array.
   */
  protected function getSearchResult(): array {
    return [
      '#theme' => 'server_theme_search_result',
      '#labels' => $this->buildLabelsFromText(['News']),
      '#title' => $this->getRandomTitle(),
      '#summary' => 'Drupal 9 starter kit for efficient and streamlined development featuring TailwindCSS!',
      '#date' => IntlDate::formatPattern(time(), 'short'),
      '#url' => Url::fromRoute('<front>'),
    ];
  }

  /**
   * Get cards.
   *
   * @return array
   *   Render array.
   */
  protected function getCards(): array {
    $image = $this->buildImage($this->getPlaceholderImage(300, 200), 'Card image');

    $card = [
      '#theme' => 'server_theme_card',
      '#image' => $image,
      '#title' => 'The source has extend, but not everyone fears it.',
      '#url' => Url::fromRoute('<front>'),
    ];

    $single_card_long_title = $card;
    $single_card_long_title['#title'] = 'How Professional Learning Networks Are Helping Educators Get Through Coronavirus, well they need to really learn a lot!';

    $items = [
      $card,
      $single_card_long_title,
      $card,
      $card,
    ];

    return [
      '#theme' => 'server_theme_cards',
      '#items' => $items,
    ];
  }

  /**
   * Define a set of buttons.
   *
   * @return array
   *   A render array containing the elements.
   */
  protected function getButtons(): array {
    $build = [];

    $url = Url::fromRoute('<front>')->toString();

    // Primary button with icon.
    $element = $this->buildButton($url, 'Download file', TRUE);
    $element['#icon'] = 'download';
    $build[] = $this->wrapElementWideContainer($element, 'Primary button');

    // Secondary button.
    $element = $this->buildButton($url, 'Register', FALSE);
    $build[] = $this->wrapElementWideContainer($element, 'Secondary button');

    return $build;
  }

  /**
   * Get the Related content carousel.
   *
   * @return array
   *   Render array.
   */
  protected function getHeroImage(): array {
    $url = Url::fromRoute('<front>')->toString();
    $button = $this->buildButton($url, 'Lean more');

    return [
      '#theme' => 'server_theme_hero_image',
      '#image' => $this->buildImage($this->getPlaceholderImage(1600, 900, '1048'), 'Hero image alt'),
      '#title' => $this->t('Drupal Starter'),
      '#subtitle' => $this->t('Drupal 9 starter kit for efficient and streamlined development featuring TailwindCSS!'),
      '#button' => $button,
    ];
  }

  /**
   * Get the Related content carousel.
   *
   * @return array
   *   Render array.
   */
  protected function getRelatedContentCarousel(): array {
    $url = Url::fromRoute('<front>')->toString();
    $button = $this->buildButton($url, 'View more');

    return [
      '#theme' => 'server_theme_related_content',
      '#title' => $this->t('Related content'),
      '#items' => $this->getRelatedContent(10),
      '#button' => $button,
    ];
  }

  /**
   * Get the footer.
   *
   * @return array
   *   Render array.
   */
  protected function getFooter(): array {
    return [
      '#theme' => 'server_theme_footer',
    ];
  }

  /**
   * Get CTA (Call to action).
   *
   * @return array
   *   Render array.
   */
  protected function getCta(): array {
    $url = Url::fromRoute('<front>')->toString();
    $button = $this->buildButton($url, 'View more');

    return [
      '#theme' => 'server_theme_paragraph__cta',
      '#title' => $this->t('Lorem ipsum dolor sit amet'),
      '#subtitle' => $this->t('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'),
      '#button' => $button,
    ];
  }

  /**
   * Build an image render array with given image URL.
   *
   * @param string $url
   *   The url of the image, internal or external.
   * @param string $alt
   *   Alt text.
   *
   * @return array
   *   An image render array.
   */
  protected function buildImage(string $url, string $alt) {
    return [
      '#theme' => 'image',
      '#uri' => $url,
      '#alt' => $alt,
    ];
  }

  /**
   * Build text with HTML.
   *
   * @param string $text
   *   The text.
   *
   * @return array
   *   A render array.
   */
  protected function buildProcessedText(string $text) {
    return [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => filter_default_format(),
    ];
  }

  /**
   * Get photographic placeholder image.
   *
   * Optionally supply an ID or a seed string to always get the same image.
   * Seeds generate a random image, but ID's can point to a specific image and
   * should be always numeric.
   *
   * @param int $width
   *   The width of the image.
   * @param int $height
   *   The height of the image.
   * @param string $id
   *   The ID of the image. Or a seed.
   * @param string $id_type
   *   The type of the ID, either 'id' or 'seed'.
   *
   * @return string
   *   URL with placeholder.
   */
  protected function getPlaceholderImage(int $width, int $height, string $id = '', string $id_type = 'id') {
    if (!empty($id)) {
      return "https://picsum.photos/{$id_type}/{$id}/{$width}/{$height}.jpg";
    }
    return "https://picsum.photos/{$width}/{$height}.jpg";
  }

  /**
   * Get placeholder image of a person.
   *
   * @param int $width
   *   The width of the image.
   * @param int $height
   *   The height of the image.
   * @param string|null $text
   *   Text to render image with.
   *
   * @return string
   *   URL with placeholder.
   */
  protected function getPlaceholderPersonImage(int $width, int $height, string $text = NULL) {
    return "https://www.fillmurray.com/{$width}/{$height}" . (!empty($text) ? '?text=' . $text : NULL);
  }

  /**
   * Get placeholder responsive image.
   *
   * @param string $responsive_image_style_id
   *   The responsive image style ID.
   *
   * @return array
   *   Render array
   */
  protected function getPlaceholderResponsiveImageStyle(string $responsive_image_style_id = 'hero'): array {
    // Load the first media image on the site.
    /** @var \Drupal\media\MediaStorage $media_storage */
    $media_storage = $this->entityTypeManager()->getStorage('media');
    $media_ids = $media_storage->getQuery()
      ->condition('bundle', 'image')
      // Get a single image.
      ->range(0, 1)
      ->execute();

    if (empty($media_ids)) {
      // No Image media.
      return [];
    }

    $media_id = key($media_ids);
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media_id);

    /** @var ?\Drupal\file\FileInterface $image */
    $image = $this->getReferencedEntityFromField($media, 'field_media_image');
    if (empty($image)) {
      // Image doesn't exist, or no access to it.
      return [];
    }

    return [
      '#theme' => 'responsive_image',
      '#uri' => $image->getFileUri(),
      '#responsive_image_style_id' => $responsive_image_style_id,
    ];
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
  public function buildMockedTag($title) {
    $dummy_term = Term::create([
      'vid' => 'example_vocabulary_machine_name',
      'name' => $title,
    ]);

    return $this->buildTag($dummy_term);
  }

  /**
   * Get a random title.
   *
   * @return string
   *   A random title.
   */
  protected function getRandomTitle(): string {
    $titles = [
      'Never Changing Will Eventually Destroy You',
      'Sick And Tired Of Doing DRUPAL The Old Way? Read This',
      '5 Brilliant Ways To Teach Your Audience About DRUPAL',
      'How To Become Better With DRUPAL In 10 Minutes',
      'Doing Drupal the Gizra way',
      'CODING And The Chuck Norris Effect',
      'The Philosophy Of CODING',
      'The Anthony Robins Guide To CODING',
      'The A - Z Guide Of CODING',
      'How To Turn CODING Into Success',
    ];
    return $titles[array_rand($titles)];
  }

  /**
   * Generate related content.
   *
   * @param int $num
   *   Number of items to create. Default 5.
   *
   * @return array
   *   Array of render arrays.
   */
  protected function getRelatedContent(int $num = 5): array {
    $element_base = [
      '#theme' => 'server_theme_card',
      '#body' => 'Decorate one package of cauliflower in six teaspoons of plain vinegar. Try flavoring the crême fraîche gingers with clammy rum and fish sauce, simmered.',
      '#url' => Url::fromRoute('<front>'),
    ];

    $elements = [];
    for ($i = 0; $i < $num; $i++) {
      $elements[] = [
        '#image' => $this->buildImage($this->getPlaceholderImage(300, 200, "card_image_$i", 'seed'), "Card image $i"),
        '#title' => $this->getRandomTitle(),
      ] + $element_base;
    }
    return $elements;
  }

}
