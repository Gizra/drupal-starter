<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\intl_date\IntlDate;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;

/**
 * Helper method for building the Node news element.
 */
trait ElementNodeNewsThemeTrait {

  use ElementWrapThemeTrait;
  use EntityDateTrait;
  use InnerElementLayoutThemeTrait;
  use LineSeparatorThemeTrait;
  use LinkThemeTrait;
  use ElementLayoutThemeTrait;
  use SocialShareThemeTrait;
  use TagThemeTrait;
  use TitleAndLabelsThemeTrait;

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
   *   The tags, rendered with `TagThemeTrait::buildElementTags`.
   * @param array $social_share
   *   The render array of the Social share buttons.
   *
   * @return array
   *   The render array.
   *
   * @throws \IntlException
   */
  protected function buildElementNodeNews(string $title, string $label, int $timestamp, array $image, array $body, array $tags, array $social_share): array {
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
      $image,
      $this->wrapProseText($body),
      $tags,
      $social_share,
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
    $elements[] = $this->wrapTextResponsiveFontSize($element, FontSizeEnum::LG);

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerMaxWidth($elements, WidthEnum::ThreeXl);
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param array $image
   *   The responsive image render array.
   * @param array $body
   *   The body render array.
   * @param array $tags
   *   The tags, rendered with `TagThemeTrait::buildElementTags`.
   * @param array $social_share
   *   The render array of the Social share buttons.
   *
   * @return array
   *   Render array
   */
  private function buildMainAndSidebar(array $image, array $body, array $tags, array $social_share): array {
    $main_elements = [];
    $sidebar_elements = [];

    $main_elements[] = $image;
    $main_elements[] = $body;

    // Get the tags, and social share.
    $sidebar_elements[] = $tags;

    // Add a line separator above the social share buttons when tags are added.
    if (!empty($tags)) {
      $sidebar_elements[] = $this->buildLineSeparator();
    }
    $sidebar_elements[] = $social_share;
    $sidebar_elements = $this->wrapContainerVerticalSpacing($sidebar_elements);

    return $this->buildElementLayoutMainAndSidebar(
      $this->wrapContainerVerticalSpacingBig($main_elements),
      $this->buildInnerElementLayout($sidebar_elements),
    );
  }

}
