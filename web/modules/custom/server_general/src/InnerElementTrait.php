<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;

/**
 * Helper methods for rendering different Card types.
 *
 * A card is used as a teaser to a piece of content. It can be an Event card,
 * a Featured content card, etc.
 *
 * Trait is providing helper methods for each card. One method equals one theme
 * file.
 */
trait InnerElementTrait {

  use ButtonTrait;
  use ElementWrapTrait;
  use InnerElementLayoutTrait;
  use LinkTrait;
  use TitleAndLabelsTrait;

  /**
   * Build "Card with image" for News content type.
   *
   * @param array $image
   *   The image render array.
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $summary
   *   Summary of the search result.
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementWithImageForNews(array $image, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];

    // Labels.
    $element = $this->buildLabelsFromText([$this->t('News')]);
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, 'gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Title as link.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, 'lg');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Body teaser.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    return $this->buildInnerElementLayoutWithImage($url, $image, $elements);
  }

  /**
   * Build "Card with image horizontal" for News content type.
   *
   * @param array $image
   *   The image render array.
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $summary
   *   Summary of the search result.
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementWithImageHorizontalForNews(array $image, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];

    // Labels.
    $element = $this->buildLabelsFromText([$this->t('News')]);
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Date.
    $element = ['#markup' => IntlDate::formatPattern($timestamp, 'short')];
    $element = $this->wrapTextColor($element, 'gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    // Title as link.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, 'lg');
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Body teaser.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    // Read more button.
    $elements[] = $this->buildButton($this->t('Read more'), $url);

    return $this->buildInnerElementLayoutWithImageHorizontal($url, $image, $elements);
  }

  /**
   * Build "Search result".
   *
   * @param string $label
   *   The label of the content type.
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $summary
   *   Summary of the search result.
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementSearchResult(string $label, string $title, Url $url, array $summary, int $timestamp): array {
    $elements = [];
    // Labels.
    $elements[] = $this->buildLabelsFromText([$label]);

    // Title as link, wrapped in h3 tag.
    $element = $this->buildLink($title, $url, 'dark-gray');
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $element = $this->wrapTextFontWeight($element, 'bold');
    $elements[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => render($element),
    ];

    // Summary.
    $elements[] = $this->wrapTextLineClamp($summary, 4);

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'short');
    $element = $this->wrapTextColor($element, 'light-gray');
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'sm');

    return $this->buildInnerElementLayout($elements);
  }

  /**
   * Build Media document card.
   *
   * @param string $title
   *   The title.
   * @param string $url
   *   The Url string.
   *
   * @return array
   *   The redner array.
   */
  protected function buildInnerElementMediaDocument(string $title, string $url): array {

    return [
      '#theme' => 'server_theme_media__document',
      '#url' => $url,
      '#title' => $title,
    ];
  }

  /**
   * Build an accordion item.
   *
   * @param string $title
   *   The title.
   * @param array $description
   *   The description render array.
   *
   * @return array
   *   The render array.
   */
  protected function buildInnerElementAccordionItem(string $title, array $description): array {

    return [
      '#theme' => 'server_theme_inner_element__accordion_item',
      '#title' => $title,
      '#description' => $this->wrapProseText($description),
    ];
  }

  /**
   * Build an Info card element.
   *
   * @param string $header
   *   The header. Usually used for the number (e.g. "100%").
   * @param string $title
   *   The title.
   * @param string|null $subtitle
   *   Optional; The subtitle.
   *
   * @return array
   *   The render array.
   */
  protected function buildInnerElementInfoCard(string $header, string $title, string $subtitle = NULL): array {
    $elements = [];

    $element = $this->wrapTextFontWeight($header, 'bold');
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $elements[] = $element;

    $bottom_elements = [];
    $element = $this->wrapTextResponsiveFontSize($title, '2xl');
    $element = $this->wrapTextCenter($element);
    $bottom_elements[] = $element;

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, 'lg');
      $element = $this->wrapTextCenter($element);
      $bottom_elements[] = $this->wrapTextColor($element, 'gray');
    }

    $bottom_elements = $this->wrapContainerVerticalSpacingTiny($bottom_elements, 'center');
    $elements[] = $bottom_elements;

    $elements = $this->wrapContainerVerticalSpacing($elements, 'center');

    return $this->buildInnerElementLayout($elements, 'light-gray');
  }

  /**
   * Build a Person teaser.
   *
   * @param string $image_url
   *   The image Url.
   * @param string $alt
   *   The image alt.
   * @param string $name
   *   The name.
   * @param string|null $subtitle
   *   Optional; The subtitle (e.g. work title).
   *
   * @return array
   *   The render array.
   */
  protected function buildInnerElementPersonTeaser(string $image_url, string $alt, string $name, string $subtitle = NULL): array {
    $elements = [];
    $element = [
      '#theme' => 'image',
      '#uri' => $image_url,
      '#alt' => $alt,
      '#width' => 100,
    ];

    $elements[] = $this->wrapRoundedCornersFull($element);

    $inner_elements = [];

    $element = $this->wrapTextFontWeight($name, 'bold');
    $inner_elements[] = $this->wrapTextCenter($element);

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
      $element = $this->wrapTextCenter($element);
      $inner_elements[] = $this->wrapTextColor($element, 'gray');
    }

    $elements[] = $this->wrapContainerVerticalSpacingTiny($inner_elements, 'center');

    return $this->buildInnerElementLayoutCentered($elements);
  }

  /**
   * Build a Person Card.
   *
   * @param string $image_url
   *   The image Url.
   * @param string $name
   *   The name.
   * @param string|null $subtitle
   *   Optional; The subtitle (e.g. work title).
   * @param string $badge
   * The badge title.
   * @param string $email
   *   The email address.
   * @param string $phone
   *   The phone number.
   *
   * @return array
   *   The render array.
   */
  protected function buildInnerElementPersonCard(string $image_url, string $name, string $subtitle = NULL, string $badge = NULL, $email= NULL, $phone = NULL): array {
    $elements = [];
    $image = [
      '#theme' => 'image',
      '#uri' => $image_url,
      '#alt' => 'The image alt ' . $name,
      '#width' => 128,
    ];

    // Image markup.
    $image = $this->wrapRoundedCornersFull($image);
    $inner_elements[] = $this->wrapContainerBottomPadding($image);

    // Text elements in the cards.
    $element = $this->wrapTextFontWeight($name, 'medium');
    $element = $this->wrapTextResponsiveFontSize($element, 'sm');
    $element = $this->wrapTextCenter($element);
    $text_elements[] = $this->wrapTextColor($element, 'darker-gray');

    if ($subtitle) {
      $element = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
      $element = $this->wrapTextCenter($element);
      $text_elements[] = $this->wrapTextColor($element, 'gray');
    }
    if ($badge) {
      $badge = $this->wrapTextResponsiveFontSize($badge, 'sm');
      $badge = $this->wrapRoundedCornersBadge($badge, 'light-green');
      $text_elements[] = $this->wrapTextColor($badge, 'dark-green');
    }
    // Wrap all the text elements together.
    $inner_elements[] = $this->wrapCardText($text_elements);
    $elements[] = $this->wrapContainerVerticalSpacingCard($inner_elements, 'center');

    // Build Contact CTAs.
    $email_text = $this->getCardCtaText($this->t('Email'));
    $phone_text = $this->getCardCtaText($this->t('Phone'));
    $email_element = ['url' => "mailto::{$email}", 'text' => $email_text ];
    $phone_element = ['url' => "mailto::{$phone}", 'text' => $phone_text ];
    $elements[] = $this->buildInnerElementContactCta($email_element, $phone_element);
    return $this->buildInnerElementLayoutCard($elements, 'white');
  }

  /**
   * Wrap an element with a tiny vertical spacing (8px).
   *
   * @param array $element
   *   Render array.
   * @param string $align
   *   Determine if flex should also have an alignment. Possible values are
   *   `start`, `center`, `end` or NULL to have no change.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementContactCta($email = [], $phone = []): array {
    return [
      '#theme' => 'server_theme_inner_element_contact_cta',
      '#email' => $email,
      '#phone' => $phone,
    ];
  }

  /**
   * Builds a Quick Link element.
   *
   * @param string $title
   *   The title.
   * @param \Drupal\Core\Url $url
   *   The Url object.
   * @param string|null $subtitle
   *   Optional; The subtitle.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementQuickLinkItem(string $title, Url $url, string $subtitle = NULL): array {
    $items = [];
    $items[] = $this->wrapTextResponsiveFontSize($title, 'xl');

    if (!empty($subtitle)) {
      $items[] = $this->wrapTextResponsiveFontSize($subtitle, 'sm');
    }

    return [
      '#theme' => 'server_theme_inner_element__quick_link_item',
      '#items' => $this->wrapContainerVerticalSpacingTiny($items),
      '#url' => $url,
    ];

  }

  /**
   * Wrap multiple cards with a grid.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCards(array $items): array {
    return [
      '#theme' => 'server_theme_cards',
      '#items' => $items,
    ];
  }

}
