<?php

namespace Drupal\server_general;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;

/**
 * Helper method for building the Node news element.
 */
trait ElementNodeNewsTrait {

  use ElementWrapTrait;
  use EntityDateTrait;
  use InnerElementLayoutTrait;
  use LineSeparatorTrait;
  use LinkTrait;
  use ElementLayoutTrait;
  use SocialShareTrait;
  use TagTrait;
  use TitleAndLabelsTrait;

  /**
   * Build the Node news element.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `News`).
   * @param int $timestamp
   *   The timestamp.
   * @param array $image
   *   The responsive image render array.
   * @param array $body
   *   The body render array.
   * @param array $tags
   *   The tags, rendered with `TagTrait::buildElementTags`.
   * @param \Drupal\Core\Url $url
   *   The Url of the node.
   *
   * @return array
   *   The render array.
   *
   * @throws \IntlException
   */
  protected function buildElementNodeNews(string $title, string $label, int $timestamp, array $image, array $body, array $tags, Url $url): array {
    $elements = [];

    // Header.
    $element = $this->buildHeader(
      $title,
      $label,
      $timestamp
    );
    $elements[] = $this->wrapContainerWide($element);

    // Main content and sidebar.
    $element = $this->buildMainAndSidebar(
      $title,
      $image,
      $this->wrapProseText($body),
      $tags,
      $url,
    );
    $elements[] = $this->wrapContainerWide($element);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerBottomPadding($elements);
  }

  /**
   * Build the header.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `News`).
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   *
   * @throws \IntlException
   */
  private function buildHeader(string $title, string $label, int $timestamp): array {
    $elements = [];

    $elements[] = $this->buildPageTitle($title);

    // Show the node type as a label.
    $elements[] = $this->buildLabelsFromText([$label]);

    // Date.
    $element = IntlDate::formatPattern($timestamp, 'long');

    // Make text bigger.
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'lg');

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerMaxWidth($elements, '3xl');
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param string $title
   *   The node title.
   * @param array $image
   *   The responsive image render array.
   * @param array $body
   *   The body render array.
   * @param array $tags
   *   The tags, rendered with `TagTrait::buildElementTags`.
   * @param \Drupal\Core\Url $url
   *   The Url of the node.
   *
   * @return array
   *   Render array
   */
  private function buildMainAndSidebar(string $title, array $image, array $body, array $tags, Url $url): array {
    $main_elements = [];
    $sidebar_elements = [];

    $main_elements[] = $image;
    $main_elements[] = $body;

    // Get the tags, and social share.
    $sidebar_elements[] = $tags;

    // Add a line separator above the social share buttons.
    $sidebar_elements[] = $this->buildLineSeparator();
    $sidebar_elements[] = $this->buildElementSocialShare($title, $url);

    $sidebar_elements = $this->wrapContainerVerticalSpacing($sidebar_elements);

    return $this->buildElementLayoutMainAndSidebar(
      $this->wrapContainerVerticalSpacingBig($main_elements),
      $this->buildInnerElementLayout($sidebar_elements),
    );
  }

}
