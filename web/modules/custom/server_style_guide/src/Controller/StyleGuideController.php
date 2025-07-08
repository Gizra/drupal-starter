<?php

namespace Drupal\server_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ThemeTrait\AccordionThemeTrait;
use Drupal\server_general\ThemeTrait\ButtonThemeTrait;
use Drupal\server_general\ThemeTrait\CardThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\ColorEnum;
use Drupal\server_general\ThemeTrait\CarouselThemeTrait;
use Drupal\server_general\ThemeTrait\CtaThemeTrait;
use Drupal\server_general\ThemeTrait\DocumentsThemeTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\ElementMediaThemeTrait;
use Drupal\server_general\ThemeTrait\ElementNodeNewsThemeTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\ExpandingTextThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\FontWeightEnum;
use Drupal\server_general\ThemeTrait\HeroThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\HtmlTagEnum;
use Drupal\server_general\ThemeTrait\InfoCardThemeTrait;
use Drupal\server_general\ThemeTrait\LinkThemeTrait;
use Drupal\server_general\ThemeTrait\NewsTeasersThemeTrait;
use Drupal\server_general\ThemeTrait\PeopleTeasersThemeTrait;
use Drupal\server_general\ThemeTrait\QuickLinksThemeTrait;
use Drupal\server_general\ThemeTrait\QuoteThemeTrait;
use Drupal\server_general\ThemeTrait\SearchThemeTrait;
use Drupal\server_general\ThemeTrait\SocialShareThemeTrait;
use Drupal\server_general\ThemeTrait\TagThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
use Drupal\server_general\WebformTrait;
use Drupal\server_style_guide\ThemeTrait\StyleGuideElementWrapThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the style-guide module.
 */
class StyleGuideController extends ControllerBase {

  use AccordionThemeTrait;
  use BuildFieldTrait;
  use ButtonThemeTrait;
  use CardThemeTrait;
  use CarouselThemeTrait;
  use CtaThemeTrait;
  use DocumentsThemeTrait;
  use ElementMediaThemeTrait;
  use ElementNodeNewsThemeTrait;
  use ElementLayoutThemeTrait;
  use ElementWrapThemeTrait;
  use ExpandingTextThemeTrait;
  use HeroThemeTrait;
  use InfoCardThemeTrait;
  use LinkThemeTrait;
  use NewsTeasersThemeTrait;
  use PeopleTeasersThemeTrait;
  use QuickLinksThemeTrait;
  use QuoteThemeTrait;
  use SearchThemeTrait;
  use SocialShareThemeTrait;
  use StyleGuideElementWrapThemeTrait;
  use TagThemeTrait;
  use TitleAndLabelsThemeTrait;
  use WebformTrait;


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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->linkGenerator = $container->get('link_generator');
    $instance->iFrameUrlHelper = $container->get('media.oembed.iframe_url_helper');
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
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
    $build[] = $this->wrapElementWideContainer($element, 'Element: Accordion');

    $element = $this->getCta();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Call to Action');

    $element = $this->getDocuments();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Documents list');

    $element = $this->getExpandingText();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Expanding text');

    $element = $this->getExpandingText(3, 'More', 'Less');
    $build[] = $this->wrapElementNoContainer($element, 'Element: Expanding text - 3 lines, custom buttons');

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

    $element = $this->getWebformElement();
    $build[] = $this->wrapElementNoContainer($element, 'Element: Webform');

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
      $items[] = $this->buildElementPersonTeaser(
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
    $result_items[] = $this->buildElementSearchResult(
      'News',
      $this->getRandomTitle(),
      Url::fromRoute('<front>'),
      $this->buildProcessedText("Both refute. Of their its it funny children into good origin into self-interest, my she were bad of chosen stage italic, fame, is must didn't evaluate little may picture the didn't is not there of high accustomed. Him great those the sort alphabet she were workmen. Reflection bad the external gloomy not we it yet any them. What's late showed picture attached duck usual. To of actual writer fame. Prepared on was to stairs basically, the see would hadn't easier searching watched in and someone his where of the and written fly being a be his the to visuals was."),
      time()
    );

    $result_items[] = $this->buildElementSearchResult(
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

      $items[] = $this->buildElementQuickLinkItem(
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
    return $this->buildElementLayoutTitleAndContent(
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

    $social_share = $this->buildElementSocialShare(
      'Social share trait',
      Url::fromUri('https://example.com'),
    );

    return $this->buildElementNodeNews(
      $this->getRandomTitle(),
      'News',
      time(),
      $image,
      $this->buildProcessedText('<p>I before parameters designer of the to separated of to part. Price question in or of a there sleep. Who a deference and drew sleep written talk said which had. sel in small been cheating sounded times should and problem. Question. Explorations derived been him aged seal for gods team- manage he according the welcoming are cities part up stands careful so own the have how up, keep</p>'),
      $tags,
      $social_share,
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

    $card = $this->buildElementNewsTeaser(
      $image,
      $title,
      $url,
      $summary,
      $timestamp
    );

    $image = $this->buildImage($this->getPlaceholderImage(300, 400));
    $title = 'A Shorter Title';
    $summary = $this->buildProcessedText('A much <strong>shorter</strong> intro');

    $card2 = $this->buildElementNewsTeaser(
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
    $link = Link::fromTextAndUrl($this->t('Home'), $url);
    $element = $this->buildButtonPrimary($link);
    $build[] = $this->wrapElementWideContainer($element, 'Primary button');

    // Secondary button.
    $link = Link::fromTextAndUrl($this->t('Register'), $url);
    $element = $this->buildButtonSecondary($link);
    $build[] = $this->wrapElementWideContainer($element, 'Secondary button');

    // Tertiary button.
    $link = Link::fromTextAndUrl($this->t('Login'), $url);
    $element = $this->buildButtonTertiary($link);
    $build[] = $this->wrapElementWideContainer($element, 'Tertiary button');

    // Download button.
    $link = Link::fromTextAndUrl($this->t('Download'), $url);
    $element = $this->buildButtonDownload($link);
    $build[] = $this->wrapElementWideContainer($element, 'Download button');

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

    $element = $this->buildLink('Internal link', $url, ColorEnum::Gray);
    $build[] = $this->wrapElementWideContainer($element, 'Link');

    $url = Url::fromUri('https://example.com');
    $element = $this->buildLink('External link', $url);
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
    $element = $this->wrapTextFontWeight($this->getRandomTitle(), FontWeightEnum::Bold);
    $build[] = $this->wrapElementWideContainer($element, 'Text decoration - Font weight');

    // Font size for an array.
    $element = [
      '#markup' => $this->getRandomTitle(),
    ];
    $element = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::LG);
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
    foreach (HtmlTagEnum::cases() as $tag) {
      $elements[] = $this->wrapHtmlTag('This is an example for ' . $tag->value, $tag);
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
      Link::fromTextAndUrl('Learn more', $url),
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

    $items[] = $this->buildElementInfoCard(
      '100%',
      'Developers like this',
      'It saves lots of dev hours, so they like to stick to it',
    );

    $items[] = $this->buildElementInfoCard(
      '2 - 5 commits',
      'Every few days there is a new PR',
    );

    $items[] = $this->buildElementInfoCard(
      '350',
      'Is a number that is likeable',
      'But there are other numbers as well',
    );

    $items[] = $this->buildElementInfoCard(
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
      $items[] = $this->buildElementDocument(
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
    $link = Link::fromTextAndUrl('View more', $url);
    $button = !$is_featured ? $this->buildButtonSecondary($link) : NULL;
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
      $items[] = $this->buildElementAccordionItem(
        $this->getRandomTitle(),
        $this->buildProcessedText('Content ' . $i . ' Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.'),
      );
    }

    return $this->buildElementAccordion(
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
      Link::fromTextAndUrl('View more', Url::fromRoute('<front>')),
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
    // Emulate core processed text by wrapping the text in div.text-formatted.
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'text-formatted',
        ],
      ],
    ];

    $element[] = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => filter_default_format(),
    ];

    return $element;
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
    $func = $is_featured ? 'buildElementNewsTeaserFeatured' : 'buildElementNewsTeaser';
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

  /**
   * Get a sample Expanding Text element.
   *
   * @return array
   *   The render array.
   */
  protected function getExpandingText(?int $lines_to_clamp = NULL, ?string $button_label_more = NULL, ?string $button_label_less = NULL): array {
    $element = ['#theme' => 'server_style_guide_text_styles'];
    $element = $this->wrapProseText($element);

    return $this->wrapContainerWide($this->buildElementExpandingText($element, $lines_to_clamp, $button_label_more, $button_label_less));
  }

  /**
   * Get Webform element.
   *
   * @return array
   *   The render array.
   */
  protected function getWebformElement(): array {
    return $this->buildWebformWithTitleAndDescription(
      $this->getWebform('contact'),
      $this->getRandomTitle(),
      $this->buildProcessedText('Decorate one package of cauliflower in six teaspoons of plain vinegar. Try flavoring the crême fraîche gingers with clammy rum and fish sauce, simmered.'),
    );
  }

}
