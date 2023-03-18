<?php

namespace Drupal\server_general;

use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;

/**
 * Helper method for building the Node news element.
 */
trait ElementNodeNewsTrait {

  use CardLayoutTrait;
  use ElementWrapTrait;
  use EntityDateTrait;
  use LineSeparatorTrait;
  use LinkTrait;
  use ElementLayoutTrait;
  use SocialShareTrait;
  use TagTrait;
  use TitleAndLabelsTrait;

  /**
   *
   */
  protected function buildElementNodeNews(
    string $title,
    string $label,
    int $timestamp,
    array $image,
    array $body,
    array $tags,
    Url $url,
  ): array {
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
      $body,
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
   * @param string $label
   * @param int $timestamp
   *
   * @return array
   *   Render array
   *
   * @throws \IntlException
   */
  protected function buildHeader(string $title, string $label, int $timestamp): array {
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
   * @param array $image
   * @param array $body
   * @param array $tags
   * @param \Drupal\Core\Url $url
   *
   * @return array
   *   Render array
   */
  protected function buildMainAndSidebar(string $title, array $image, array $body, array $tags, Url $url): array {
    $main_elements = [];
    $sidebar_elements = [];
    $social_share_elements = [];

    $main_elements[] = $image;
    $main_elements[] = $body;

    // Get the tags, and social share.
    $sidebar_elements[] = $tags;

    // Add a line separator above the social share buttons.
    $social_share_elements[] = $this->buildLineSeparator();
    $social_share_elements[] = $this->buildElementSocialShare($title, $url);

    $sidebar_elements[] = $this->wrapContainerVerticalSpacing($social_share_elements);
    $sidebar_elements = $this->wrapContainerVerticalSpacingBig($sidebar_elements);

    return $this->buildElementLayoutMainAndSidebar(
      $this->wrapContainerVerticalSpacingBig($main_elements),
      $this->buildCardLayout($sidebar_elements),
    );
  }

}
