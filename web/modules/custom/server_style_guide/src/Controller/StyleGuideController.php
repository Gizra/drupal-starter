<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\media\IFrameUrlHelper;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\CardTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\LinkTrait;
use Drupal\server_general\MediaVideoTrait;
use Drupal\server_general\TagTrait;
use Drupal\server_general\TitleAndLabelsTrait;
use Drupal\server_style_guide\StyleGuideElementWrapTrait;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use BuildFieldTrait;
  use ButtonTrait;
  use CardTrait;
  use ElementWrapTrait;
  use LinkTrait;
  use MediaVideoTrait;
  use StyleGuideElementWrapTrait;
  use TagTrait;
  use TitleAndLabelsTrait;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The iFrame URL helper service, used for embedding videos.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Class constructor.
   */
  public function __construct(LinkGenerator $link_generator, IFrameUrlHelper $iframe_url_helper) {
    $this->linkGenerator = $link_generator;
    $this->iFrameUrlHelper = $iframe_url_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('link_generator'),
      $container->get('media.oembed.iframe_url_helper'),
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

    $build[] = $this->getLinks();

    $build[] = $this->getTextDecorations();

    $element = $this->getCards();
    $build[] = $this->wrapElementWideContainer($element, 'Card: Simple (Search result)');

    $element = $this->getCardsCentered();
    $build[] = $this->wrapElementWideContainer($element, 'Card: Centered (Profile info)');

    $element = $this->getCardsWithImageForNews();
    $build[] = $this->wrapElementWideContainer($element, 'Cards: With image (News cards)');

    $element = $this->getCardsWithImageHorizontalForNews();
    $build[] = $this->wrapElementWideContainer($element, 'Cards: Horizontal with image (Featured content)');

    $element = $this->getTags();
    $build[] = $this->wrapElementWideContainer($element, 'Tags');

    $element = $this->getMediaImage();
    $build[] = $this->wrapElementWideContainer($element, 'Media: Image');

    $element = $this->getMediaVideo();
    $build[] = $this->wrapElementWideContainer($element, 'Media: Video');

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
   * Get Simple cards.
   *
   * @return array
   *   Render array.
   */
  protected function getCards(): array {
    $elements = [];
    $elements[] = $this->buildCardSearchResult(
      'News',
      $this->getRandomTitle(),
      Url::fromRoute('<front>'),
      $this->buildProcessedText("Both refute. Of their its it funny children into good origin into self-interest, my she were bad of chosen stage italic, fame, is must didn't evaluate little may picture the didn't is not there of high accustomed. Him great those the sort alphabet she were workmen. Reflection bad the external gloomy not we it yet any them. What's late showed picture attached duck usual. To of actual writer fame. Prepared on was to stairs basically, the see would hadn't easier searching watched in and someone his where of the and written fly being a be his the to visuals was."),
      time()
    );

    $elements[] = $this->buildCardSearchResult(
      'News',
      $this->getRandomTitle(),
      Url::fromRoute('<front>'),
      $this->buildProcessedText("How does the system generate all this custom content?"),
      time()
    );

    return $this->wrapContainerVerticalSpacingBig($elements);
  }

  /**
   * Get Centered cards.
   *
   * @return array
   *   Render array.
   */
  protected function getCardsCentered(): array {
    $items = [];
    $url = Url::fromRoute('<front>');

    $names = ['Jon Doe', 'Smith Allen', 'David Bowie'];
    foreach ($names as $name) {
      $elements = [];
      $element = [
        '#theme' => 'image',
        '#uri' => $this->getPlaceholderPersonImage(100),
        '#width' => 100,
      ];

      // Image should be clickable.
      $element = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => render($element),
        '#attributes' => ['href' => $url->toString()],
      ];

      $elements[] = $this->wrapRoundedCornersFull($element);

      $element = $this->buildLink($name, $url);
      $element = $this->wrapTextFontWeight($element, 'bold');
      $element = $this->wrapTextCenter($element);
      $elements[] = $this->wrapTextColor($element, 'light-gray');

      $items[] = $this->buildCardCentered($elements);
    }

    return $this->buildCards($items);

  }

  /**
   * Get Media image.
   *
   * @return array
   *   Render array.
   */
  protected function getMediaImage(): array {
    $image = $this->buildImage($this->getPlaceholderImage(300, 200), 'Image');

    $caption = [
      '#theme' => 'server_theme_media_caption',
      '#caption' => 'This is the caption of the image',
    ];

    return [
      '#theme' => 'server_theme_media__image',
      '#image' => $image,
      '#caption' => $caption,
    ];
  }

  /**
   * Get Media video.
   *
   * @return array
   *   Render array.
   */
  protected function getMediaVideo(): array {
    $caption = [
      '#theme' => 'server_theme_media_caption',
      '#caption' => 'This is the caption of the video',
    ];

    return [
      '#theme' => 'server_theme_media__video',
      '#video' => $this->buildVideo('https://www.youtube.com/watch?v=dSZQNOvpszQ', 650, 400),
      '#caption' => $caption,
    ];
  }

  /**
   * Get cards with image.
   *
   * @return array
   *   Render array.
   */
  protected function getCardsWithImageForNews(): array {
    $image = $this->buildImage($this->getPlaceholderImage(300, 200));
    $title = 'Never Changing Will Eventually Destroy You, But then You Should See The Longest Title, This one works. check the below one , ideally speaking it, pretty amazing eh, you will see';
    $url = Url::fromRoute('<front>');
    $summary = $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>');
    $timestamp = time();

    $card = $this->buildCardWithImageForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $image = $this->buildImage($this->getPlaceholderImage(300, 400));
    $title = 'A Shorter Title';
    $summary = $this->buildProcessedText('A much <strong>shorter</strong> intro');

    $card2 = $this->buildCardWithImageForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $items = [
      $card,
      $card2,
      $card,
      $card2,
    ];

    return $this->buildCards($items);
  }

  /**
   * Get cards with image.
   *
   * @return array
   *   Render array.
   */
  protected function getCardsWithImageHorizontalForNews(): array {
    $image = $this->buildImage($this->getPlaceholderImage(400, 300));
    $title = 'Never Changing Will Eventually Destroy You, But then You Should See The Longest Title, This one works. check the below one , ideally speaking it, pretty amazing eh, you will see';
    $url = Url::fromRoute('<front>');
    $summary = $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>');
    $timestamp = time();

    $card = $this->buildCardWithImageHorizontalForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $image = $this->buildImage($this->getPlaceholderImage(400, 300));
    $title = 'A Shorter Title';
    $summary = $this->buildProcessedText('A much <strong>shorter</strong> intro');

    $card2 = $this->buildCardWithImageHorizontalForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $items = [
      $card,
      $card2,
    ];

    $element = $this->wrapContainerVerticalSpacingBig($items);
    return $this->wrapContainerNarrow($element);
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
    $element = $this->buildButton('Download file', $url, TRUE);
    $element['#icon'] = 'download';
    $build[] = $this->wrapElementWideContainer($element, 'Primary button');

    // Secondary button.
    $element = $this->buildButton('Register', $url, FALSE);
    $build[] = $this->wrapElementWideContainer($element, 'Secondary button');

    return $build;
  }

  /**
   * Get a set of buttons.
   *
   * @return array
   *   A render array.
   */
  protected function getLinks(): array {
    $build = [];

    $url = Url::fromRoute('<front>');

    $element = $this->buildLink('Internal link', $url, 'gray');
    $build[] = $this->wrapElementWideContainer($element, 'Link');

    $url = 'https://google.com';
    $element = $this->buildLink('External link', $url, 'dark-gray', NULL, 'hover');
    $build[] = $this->wrapElementWideContainer($element, 'External link');

    return $build;
  }

  /**
   * Get text decorations (font weight, font size, etc.).
   *
   * @return array
   *   A render array.
   */
  protected function getTextDecorations(): array {
    $build = [];

    // Font weight for a string.
    $element = $this->wrapTextFontWeight($this->getRandomTitle(), 'bold');
    $build[] = $this->wrapElementWideContainer($element, 'Text decoration - Font weight');

    // Font size for an array.
    $element = [
      '#markup' => $this->getRandomTitle(),
    ];
    $element = $this->wrapTextResponsiveFontSize($element, 'lg');
    $build[] = $this->wrapElementWideContainer($element, 'Text decoration - Font size');

    // Italic format for `TranslatableMarkup`.
    $element = $this->wrapTextItalic($this->t('TranslatableMarkup should be decorated as well'));
    $build[] = $this->wrapElementWideContainer($element, 'Text decoration - Italic');

    // Underline.
    $element = $this->wrapTextUnderline($this->getRandomTitle());
    $build[] = $this->wrapElementWideContainer($element, 'Text decoration - Underline');

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
    $button = $this->buildButton('Learn more', $url);

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
    $button = $this->buildButton('View more', $url);

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
  protected function buildImage(string $url, string $alt = '') {
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
   * @param int $width_and_height
   *   The width and height of the image.
   *
   * @return string
   *   URL with placeholder.
   */
  protected function getPlaceholderPersonImage(int $width_and_height) {
    $unique_id = substr(str_shuffle(md5(microtime())), 0, 10);
    return "https://i.pravatar.cc/{$width_and_height}?u=" . $unique_id;
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
    $elements = [];
    for ($i = 0; $i < $num; $i++) {
      $elements[] =$this->buildCardWithImageForNews(
        $this->buildImage($this->getPlaceholderImage(300, 200, "card_image_$i", 'seed'), "Card image $i"),
        $this->getRandomTitle(),
        Url::fromRoute('<front>'),
        $this->buildProcessedText('Decorate one package of cauliflower in six teaspoons of plain vinegar. Try flavoring the crême fraîche gingers with clammy rum and fish sauce, simmered.'),
        time(),
      );
    }
    return $elements;
  }

}
