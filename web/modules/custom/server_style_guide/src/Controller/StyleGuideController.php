<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\server_general\TagBuilderTrait;
use Drupal\server_style_guide\ElementWrapTrait;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use ElementWrapTrait;
  use TagBuilderTrait;

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

    $single_card_image_random = $single_card_simple;
    $single_card_image_random['#url'] = Url::fromUri('https://www.example.com/test')->toString();
    $single_card_image_random['#image_alt'] = $this->t('Image alt');
    // Get a random photographic image.
    $single_card_image_random['#image'] = $this->getPlaceholderImage(256, 128);

    $single_card_image_id = $single_card_no_body;
    $single_card_image_id['#url'] = Url::fromUri('https://www.example.com/test')->toString();
    $single_card_image_id['#image_alt'] = $this->t('Image alt');
    // Get a static photographic image with ID 1043.
    // See list of all images at: https://picsum.photos/images.
    $single_card_image_id['#image'] = $this->getPlaceholderImage(256, 128, '1043');

    $single_card_image_seed = $single_card_long_title;
    $single_card_image_seed['#url'] = Url::fromUri('https://www.example.com/test')->toString();
    $single_card_image_seed['#image_alt'] = $this->t('Image alt');
    // When you use a seed a random image is generated for a certain string,
    // and if the same string is used again the same image will always be
    // returned. Hence it's 'random' but also 'static'.
    $single_card_image_seed['#image'] = $this->getPlaceholderImage(256, 128, 'drupal-starter', 'seed');

    $single_card_image_seed_author_name = $single_card_long_author_name;
    $single_card_image_seed_author_name['#url'] = Url::fromUri('https://www.example.com/test')->toString();
    $single_card_image_seed_author_name['#image_alt'] = $this->t('Image alt');
    $single_card_image_seed_author_name['#image'] = $this->getPlaceholderImage(256, 128, 'single_card_long_author_name', 'seed');

    $items = [
      $single_card_simple,
      $single_card_no_body,
      $single_card_long_title,
      $single_card_long_author_name,
      $single_card_image_random,
      $single_card_image_id,
      $single_card_image_seed,
      $single_card_image_seed_author_name,
    ];

    $element = [
      '#theme' => 'server_theme_cards',
      '#items' => $items,
    ];
    $build[] = $this->wrapElementWideContainer($element, 'Cards');

    $element = [
      '#theme' => 'server_theme_content__tags',
      '#tags' => $many_tags,
    ];
    $build[] = $this->wrapElementWideContainer($element, 'Content Tags');

    $element = [
      '#theme' => 'server_theme_content__image_and_teaser',
      '#image' => $this->getPlaceholderImage(940, 265),
      '#teaser' => [
        '#type' => 'processed_text',
        '#text' => 'Diatrias favere! Sunt tataes <strong>visum superbus</strong>, clemens mineralises. Who can need the acceptance and afterlife of a doer if he has the abstruse issue of the self?',
        '#format' => filter_default_format(),
      ],
    ];
    $build[] = $this->wrapElementWideContainer($element, 'Content Image and Teaser');

    $element = [
      '#theme' => 'server_theme_user_image',
      '#image' => $this->getPlaceholderPersonImage(256, 256),
      '#image_alt' => 'Bill Murray',
      '#url' => '#',
    ];
    $build[] = $this->wrapElementWideContainer($element, 'User Image - With Photo');

    $element = [
      '#theme' => 'server_theme_user_image',
      '#initials' => 'BM',
      '#url' => '#',
    ];
    $build[] = $this->wrapElementWideContainer($element, 'User Image - No Photo');

    $element = [
      '#theme' => 'server_theme_page_title',
      '#title' => 'The source has extend, but not everyone fears it',
    ];
    $build[] = $this->wrapElementWideContainer($element, 'Page Title');

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

    $element = [
      '#theme' => 'server_theme_hero_image',
      '#image' => $this->buildImage($this->getPlaceholderImage(1600, 900, '1048'), 'Hero image alt'),
      '#title' => $this->t('Drupal Starter'),
      '#subtitle' => $this->t('Drupal 9 starter kit for efficient and streamlined development featuring TailwindCSS!'),
      '#url' => $this->getSampleUrl(),
      '#url_title' => $this->t('Learn more'),
    ];
    $build[] = $this->wrapElementNoContainer($element, 'Hero image');

    $element = [
      '#theme' => 'server_theme_related_content',
      '#title' => $this->t('Related content'),
      '#items' => $this->getRelatedContent(10),
      '#url' => $this->getSampleUrl(),
      '#url_title' => $this->t('View more'),
    ];
    $build[] = $this->wrapElementNoContainer($element, 'Related content');

    $element = [
      '#theme' => 'server_theme_footer',
    ];
    $build[] = $this->wrapElementNoContainer($element, 'Footer');

    $element = [
      '#theme' => 'server_theme_cta',
      '#title' => $this->t('Lorem ipsum dolor sit amet'),
      '#subtitle' => $this->t('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'),
      '#url' => Url::fromRoute('<front>'),
      '#url_title' => $this->t('Button title'),
    ];
    $build[] = $this->wrapElementNoContainer($element, 'Call to Action');

    return $build;
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

  /**
   * Get a sample URL object.
   *
   * @return \Drupal\Core\Url
   *   The sample generated URL object.
   */
  protected function getSampleUrl(): Url {
    return Url::fromUri('https://www.example.com');
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
      '#url' => $this->getSampleUrl(),
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
