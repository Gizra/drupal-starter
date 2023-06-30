<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\media\IFrameUrlHelper;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\InnerElementTrait;
use Drupal\server_general\ElementNodeNewsTrait;
use Drupal\server_general\ElementTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\LinkTrait;
use Drupal\server_general\ElementMediaTrait;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\TagTrait;
use Drupal\server_general\TitleAndLabelsTrait;
use Drupal\server_style_guide\StyleGuideElementWrapTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use BuildFieldTrait;
  use ButtonTrait;
  use ElementTrait;
  use ElementWrapTrait;
  use ElementMediaTrait;
  use InnerElementTrait;
  use LinkTrait;
  use ElementNodeNewsTrait;
  use SocialShareTrait;
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

    $build[] = [
      '#theme' => 'server_style_guide_wrapper',
      '#elements' => $this->getAllElements(),
    ];

    $build['#attached']['library'][] = 'server_style_guide/accordion';

    return $build;
  }

  /**
   * Get all the elements that should be in the Style guide.
   *
   * @return array
   *   A render array containing the elements.
   */
  protected function getAllElements() : array {
    $build = [];

    $element = $this->getPageTitle();
    $build[] = $this->wrapElementWideContainer($element, 'Page title');

    $build[] = $this->getButtons();

    $build[] = $this->getLinks();

    $element = $this->getTags();
    $build[] = $this->wrapElementWideContainer($element, 'Tags');

    $element = $this->getSocialShare();
    $build[] = $this->wrapElementWideContainer($element, 'Social share');

    $build[] = $this->getTextDecorations();

    $build[] = $this->getTextStyles();

    $element = $this->getAccordion();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Accordion');

    $element = $this->getCta();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Call to Action');

    $element = $this->getDocuments();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Documents list');

    $element = $this->getHeroImage();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Hero image');

    $element = $this->getInfoCards();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Info cards');

    $element = $this->getMediaImage();
    $build[] = $this->wrapElementWideContainer($element, 'Element: Media Image (Embed in text field)');

    $element = $this->getMediaImageWithCreditOverlay();
    $build[] = $this->wrapElementWideContainer($element, 'Element: Media Image with credit overlay (Hero on Node news)');

    $element = $this->getMediaVideo();
    $build[] = $this->wrapElementWideContainer($element, 'Element: Media Video');

    $element = $this->getNewsTeasers();
    $build[] = $this->wrapElementNoContainer($element, 'Element: News teasers');

    $element = $this->getParagraphTitleAndText();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Paragraph title and text');

    $element = $this->getPeopleTeasers();
    $build[] = $this->wrapElementNoContainer($element, 'Element: People teasers');

    $element = $this->getPersonCard();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Person card');

    $element = $this->getPeopleCards();
    $build[] = $this->wrapElementNoContainer($element, 'Element: People cards');

    $element = $this->getQuote();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Quote');

    $element = $this->getQuickLinks();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Quick links');

    $element = $this->getRelatedContentCarousel(FALSE);
    $build[] = $this->wrapElementNoContainer($element, 'Element: Related content (Carousel, not featured)');

    $element = $this->getRelatedContentCarousel(TRUE);
    $build[] = $this->wrapElementNoContainer($element, 'Element: Related content (Carousel, featured)');

    $element = $this->getSearchTermFacetsAndResults();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Search term, facets and results');

    $element = $this->getNodeNews();
    $build[] = $this->wrapElementNoContainer($element, 'Node view: News');

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
    $url = Url::fromRoute('<front>');

    $items = [
      $this->buildTag('The transporter', $url),
      $this->buildTag('Is more girl', $url),
    ];

    return $this->buildElementTags(
      'Tags',
      $items,
    );
  }

  /**
   * Get tags.
   *
   * @return array
   *   Render array.
   */
  protected function getSocialShare(): array {

    return $this->buildElementSocialShare(
      'Social share trait',
      Url::fromUri('https://example.com'),
    );
  }

  /**
   * Get People teasers element.
   *
   * @return array
   *   Render array.
   */
  protected function getPeopleTeasers(): array {
    $items = [];

    $names = [
      'Jon Doe',
      'Smith Allen',
      'David Bowie',
      'Rick Morty',
    ];
    foreach ($names as $key => $name) {
      $items[] = $this->buildInnerElementPersonTeaser(
        $this->getPlaceholderPersonImage(100),
        'The image alt ' . $name,
        $name,
        $key === 1 ? 'General Director, and Assistant to The Regional Manager' : NULL,
      );

    }

    return $this->buildElementPeopleTeasers(
      $this->getRandomTitle(),
      $this->buildProcessedText('This is a directory list of awesome people'),
      $items,
    );
  }

  /**
     * Get Person Card element.
     *
     * @return array
     *   Render array.
     */
  protected function getPersonCard(): array {
    $person = [
      'name' => 'Jon Doe',
      'email' => 'JonDoe@example.com',
      'subtitle' => 'Paradigm Representative',
      'badge' => 'Admin',
      'phone' => "(555) 555-1234",
    ];
    $items[] = $this->buildInnerElementPersonCard(
      $this->getPlaceholderPersonImage(128),
      $person['name'],
      $person['subtitle'],
      $person['badge'],
      $person['email'],
      $person['phone'],
    );
    $cards = $this->buildElementPeopleCards(
      $this->getRandomTitle(),
      [],
      $items,
    );

    // Add gray background for prominent box shadow.
    return $this->wrapContainerWide($cards, 'light-gray');
  }

  /**
   * Get People Card element.
   *
   * @return array
   *   Render array.
   */
  protected function getPeopleCards(): array {
    $items = [];
    $people = [
      [
        'name' => 'Jane Cooper',
        'email' => 'janeCooper@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
        'badge' => 'Admin',
      ],
      [
        'name' => 'Smith Allen',
        'email' => 'SmithAllen@example.com',
        'phone' => "(555) 555-1234",
        'badge' => 'Admin',
      ],
      [
        'name' => 'Rick Morty',
        'email' => 'RickMorty@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
        'badge' => 'Admin',
      ],
      [
        'name' => 'David Bowie',
        'email' => 'DavidBowie@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
        'badge' => 'Admin',
      ],
      [
        'name' => 'Smith John',
        'email' => 'SmithAllen@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
      [
        'name' => 'Jon Doe',
        'email' => 'JonDoe@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
      [
        'name' => 'David Bowie',
        'email' => 'DavidBowie@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
      [
        'name' => 'Rick Morty',
        'email' => 'RickMorty@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
      [
        'name' => 'Smith Locke',
        'email' => 'SmithAllen@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
      [
        'name' => 'Jon Doom',
        'email' => 'JonDoe@example.com',
        'phone' => "(555) 555-1234",
        'subtitle' => 'Paradigm Representative',
      ],
    ];
    foreach ($people as $person) {
      $items[] = $this->buildInnerElementPersonCard(
        $this->getPlaceholderPersonImage(128),
        $person['name'],
        $person['subtitle'] ?? '',
        $person['badge'] ?? '',
        $person['email'],
        $person['phone'],
      );
    }

    $cards = $this->buildElementPeopleCards(
      $this->getRandomTitle(),
      [],
      $items,
    );

    // Gray background for prominent box shadow.
    return $this->wrapContainerWide($cards, 'light-gray');
  }

  /**
   * Get Media image with credit and caption.
   *
   * @return array
   *   Render array.
   */
  protected function getMediaImage(): array {
    $image = $this->buildImage($this->getPlaceholderImage(300, 200));

    return $this->buildElementImage(
      $image,
      'This is the Credit of the image',
      'This is the Caption of the image',
    );
  }

  /**
   * Get Media image with credit overlay.
   *
   * @return array
   *   Render array.
   */
  protected function getMediaImageWithCreditOverlay(): array {
    $image = $this->buildImage($this->getPlaceholderImage(780, 250));

    return $this->buildElementImageWithCreditOverlay(
      $image,
      'This is the Credit of the image',
    );
  }

  /**
   * Get Media video.
   *
   * @return array
   *   Render array.
   */
  protected function getMediaVideo(): array {

    return $this->buildElementVideo(
      'https://www.youtube.com/watch?v=dSZQNOvpszQ',
      650,
      400,
      FALSE,
      'This is the Credit of the video',
      'This is the Caption of the video',
    );
  }

  /**
   * Get Search term, facets and results.
   *
   * @return array
   *   Render array.
   */
  protected function getSearchTermFacetsAndResults(): array {
    $result_items = [];
    $result_items[] = $this->buildInnerElementSearchResult(
      'News',
      $this->getRandomTitle(),
      Url::fromRoute('<front>'),
      $this->buildProcessedText("Both refute. Of their its it funny children into good origin into self-interest, my she were bad of chosen stage italic, fame, is must didn't evaluate little may picture the didn't is not there of high accustomed. Him great those the sort alphabet she were workmen. Reflection bad the external gloomy not we it yet any them. What's late showed picture attached duck usual. To of actual writer fame. Prepared on was to stairs basically, the see would hadn't easier searching watched in and someone his where of the and written fly being a be his the to visuals was."),
      time()
    );

    $result_items[] = $this->buildInnerElementSearchResult(
      'News',
      $this->getRandomTitle(),
      Url::fromRoute('<front>'),
      $this->buildProcessedText("How does the system generate all this custom content?"),
      time()
    );

    return $this->buildElementSearchTermFacetsAndResults(
      // We can't easily theme the facets, so we skip that part on the style
      // guide.
      [],
      FALSE,
      $result_items,
      'The search query',
    );
  }

  /**
   * Get the Quote element.
   *
   * @return array
   *   Render array.
   */
  protected function getQuote(): array {

    return $this->buildElementQuote(
      $this->buildImage($this->getPlaceholderImage(1280, 400)),
      $this->buildProcessedText("I before parameters designer of the to separated of to part, price question in or of a there sleep."),
      'General Director, and Assistant to The Regional Manager',
      'This is a photo credit',
    );
  }

  /**
   * Get Quick links element.
   *
   * @return array
   *   Render array.
   */
  protected function getQuickLinks(): array {
    $url = Url::fromRoute('<front>');
    $items = [];
    $i = 1;
    while ($i <= 4) {
      $subtitle = $i == 2 ? 'This is a quick link description' : NULL;

      $items[] = $this->buildInnerElementQuickLinkItem(
        $this->getRandomTitle(),
        $url,
        $subtitle,
      );

      ++$i;
    }

    return $this->buildElementQuickLinks(
      $this->t('Quick Links'),
      $this->buildProcessedText('The Quick links description'),
      $items,
    );
  }

  /**
   * Get Paragraph title and text element.
   *
   * @return array
   *   Render array.
   */
  protected function getParagraphTitleAndText(): array {
    return $this->buildElementParagraphTitleAndText(
      $this->getRandomTitle(),
      $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>'),
    );
  }

  /**
   * Build Node news element.
   *
   * @return array
   *   The render array.
   */
  protected function getNodeNews(): array {
    // Image (Media) and Tags are referenced entities, so we have to render them
    // before passing them on.
    $image = $this->buildElementImageWithCreditOverlay(
      $this->buildImage($this->getPlaceholderImage(800, 240)),
      'This is the photo credit',
    );

    $tags = $this->getTags();

    return $this->buildElementNodeNews(
      $this->getRandomTitle(),
      'News',
      time(),
      $image,
      $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>'),
      $tags,
      Url::fromRoute('<front>', [], ['absolute' => TRUE]),
    );
  }

  /**
   * Get "News teasers" element.
   *
   * @return array
   *   Render array.
   */
  protected function getNewsTeasers(): array {
    $image = $this->buildImage($this->getPlaceholderImage(300, 200));
    $title = 'Never Changing Will Eventually Destroy You, But then You Should See The Longest Title, This one works. check the below one , ideally speaking it, pretty amazing eh, you will see';
    $url = Url::fromRoute('<front>');
    $summary = $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>');
    $timestamp = time();

    $card = $this->buildInnerElementWithImageForNews(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $image = $this->buildImage($this->getPlaceholderImage(300, 400));
    $title = 'A Shorter Title';
    $summary = $this->buildProcessedText('A much <strong>shorter</strong> intro');

    $card2 = $this->buildInnerElementWithImageForNews(
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

    return $this->buildElementNewsTeasers(
      $this->getRandomTitle(),
      $this->buildProcessedText('The News teasers <em>description</em>'),
      // We're mimicking what we have in
      // `views-view-unformatted--news.html.twig`.
      $this->buildCards($items),
    );
  }

  /**
   * Define a set of buttons.
   *
   * @return array
   *   A render array containing the elements.
   */
  protected function getButtons(): array {
    $build = [];

    $url = Url::fromRoute('<front>');

    // Primary button with icon.
    $element = $this->buildButton('Download file', $url, TRUE, 'download');
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

    $url = Url::fromUri('https://example.com');
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
   * Get text styles prose, and non-prose.
   *
   * @return array
   *   A render array.
   */
  protected function getTextStyles(): array {
    $build = [];

    // For non-prose text we will use the element wrappers.
    $elements = [];

    // Wrap Html tag from h1 to h5.
    foreach (range(1, 5) as $index) {
      $tag = 'h' . $index;
      $elements[] = $this->wrapHtmlTag('This is an example for ' . $tag, $tag);
    }
    $build[] = $this->wrapElementWideContainer($elements, 'Headings (h1 - h5)');

    $element = ['#theme' => 'server_style_guide_text_styles'];
    $element = $this->wrapProseText($element);
    $build[] = $this->wrapElementWideContainer($element, 'Text styles (Prose)');

    return $build;
  }

  /**
   * Get the Hero image element.
   *
   * @return array
   *   Render array.
   */
  protected function getHeroImage(): array {
    $url = Url::fromRoute('<front>');

    return $this->buildElementHeroImage(
      $this->buildImage($this->getPlaceholderImage(1600, 400)),
      $this->getRandomTitle(),
      $this->getRandomTitle(),
      'Learn more',
      $url,
    );
  }

  /**
   * Get the Info cards element.
   *
   * @return array
   *   Render array.
   */
  protected function getInfoCards(): array {
    $items = [];

    $items[] = $this->buildInnerElementInfoCard(
      '100%',
      'Developers like this',
      'It saves lots of dev hours, so they like to stick to it',
    );

    $items[] = $this->buildInnerElementInfoCard(
      '2 - 5 commits',
      'Every few days there is a new PR',
    );

    $items[] = $this->buildInnerElementInfoCard(
      '350',
      'Is a number that is likeable',
      'But there are other numbers as well',
    );

    $items[] = $this->buildInnerElementInfoCard(
      '2 - 5 commits',
      'Every few days there is a new PR',
      'Sometimes there are even more!',
    );

    return $this->buildElementInfoCards(
      $this->getRandomTitle(),
      $this->buildProcessedText('This is the <strong>description</strong> of the info cards element'),
      $items,
    );
  }

  /**
   * Get the Documents list.
   *
   * @return array
   *   Render array.
   */
  protected function getDocuments(): array {
    $items = [];
    $i = 1;
    while ($i <= 8) {
      // Add documents.
      $items[] = $this->buildInnerElementMediaDocument(
        $this->getRandomTitle(),
        Url::fromUserInput('/modules/custom/server_migrate/files/drupal-starter.pdf')->toString(),
      );
      ++$i;
    }

    return $this->buildElementDocuments(
      $this->getRandomTitle(),
      $this->buildProcessedText('Documents list subtitle is this line'),
      $items,
    );
  }

  /**
   * Get the Related content carousel.
   *
   * @param bool $is_featured
   *   Determine if carousel should render related content as featured items
   *   (horizontal card with image).
   *
   * @return array
   *   Render array.
   */
  protected function getRelatedContentCarousel(bool $is_featured): array {
    $url = Url::fromRoute('<front>');

    // Show button only if it's not featured content.
    $button = !$is_featured ? $this->buildButton('View more', $url) : NULL;
    $items = $this->getRelatedContent(6, $is_featured);

    return $this->buildElementCarousel(
      $this->t('Related content'),
      $this->buildProcessedText('Description of the related content'),
      $items,
      $is_featured,
      $button,
    );
  }

  /**
   * Generate an Accordion element.
   *
   * @return array
   *   Render array.
   */
  protected function getAccordion(): array {
    $items = [];

    for ($i = 0; $i < 7; $i++) {
      // Add accordion items.
      $items[] = $this->buildInnerElementAccordionItem(
        $this->getRandomTitle(),
        $this->buildProcessedText('Content ' . $i . ' Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'),
      );
    }

    return $this->buildElementAccordion(
      $this->getRandomTitle(),
      $this->buildProcessedText('This is the main description of the FAQ section'),
      $items,
    );
  }

  /**
   * Get CTA.
   *
   * @return array
   *   Render array.
   */
  protected function getCta(): array {
    return $this->buildElementCta(
      $this->getRandomTitle(),
      $this->buildProcessedText('How does the system generate all this custom content? It actually skims Wikipedia pages related to your search'),
      'View more',
      Url::fromRoute('<front>'),
    );

  }

  /**
   * Build an image render array with given image URL.
   *
   * @param string $url
   *   The url of the image, internal or external.
   *
   * @return array
   *   An image render array.
   */
  protected function buildImage(string $url) {
    return [
      '#theme' => 'image',
      '#uri' => $url,
      '#alt' => 'Placeholder image',
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
   * Get a random title.
   *
   * @return string
   *   A random title.
   */
  protected function getRandomTitle(): string {
    $titles = [
      'Never Changing Will Eventually Destroy You',
      'Sick And Tired Of Doing Drupal The Old Way? Read This',
      '5 Brilliant Ways To Teach Your Audience About Drupal',
      'How To Become Better With Drupal In 10 Minutes',
      'Using Pluggable Entity View Builder for Drupal theming',
      'Coding And The Chuck Norris Effect',
      'The Philosophy Of Coding',
      'The Anthony Robins Guide To Coding',
      'The A - Z Guide Of Coding',
      'How To Turn Coding Into Success',
    ];
    return $titles[array_rand($titles)];
  }

  /**
   * Generate related content.
   *
   * @param int $num
   *   Number of items to create. Default 5.
   * @param bool $is_featured
   *   Determine if carousel should render related content as featured items
   *   (horizontal card with image). Defaults to FALSE.
   *
   * @return array
   *   Array of render arrays.
   */
  protected function getRelatedContent(int $num = 5, bool $is_featured = FALSE): array {
    $elements = [];
    $func = $is_featured ? 'buildInnerElementWithImageHorizontalForNews' : 'buildInnerElementWithImageForNews';
    for ($i = 0; $i < $num; $i++) {
      $elements[] = call_user_func(
        [$this, $func],
        $this->buildImage($this->getPlaceholderImage(300, 200, "card_image_$i", 'seed')),
        $this->getRandomTitle(),
        Url::fromRoute('<front>'),
        $this->buildProcessedText('Decorate one package of cauliflower in six teaspoons of plain vinegar. Try flavoring the crême fraîche gingers with clammy rum and fish sauce, simmered.'),
        time(),
      );
    }
    return $elements;
  }

  protected function getCardCtaText($text) {
    return $this->wrapTextResponsiveFontSize($text, 'sm');
  }

}
