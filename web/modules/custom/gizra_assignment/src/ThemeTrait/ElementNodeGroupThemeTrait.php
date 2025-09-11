<?php

declare(strict_types=1);

namespace Drupal\gizra_assignment\ThemeTrait;

use Drupal\intl_date\IntlDate;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\ThemeTrait\TagThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;
use Drupal\server_general\ThemeTrait\LinkThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\SocialShareThemeTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\LineSeparatorThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
use Drupal\server_general\ThemeTrait\InnerElementLayoutThemeTrait;

/**
 * Helper method for building the Node group element.
 */
trait ElementNodeGroupThemeTrait {

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
   * Build the Node group element.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `Group`).
   * @param int $timestamp
   *   The timestamp.
   * @param array $image
   *   The responsive image render array.
   * @param array $greetings
   *   The greetings render array.
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
  protected function buildElementNodeGroup(string $title, string $label, int $timestamp, array $image, array $greetings, array $body, array $tags, array $social_share): array {
    $elements = [];

    // Header.
    $element = $this->buildHeader(
      $title,
      $label,
      $timestamp,
    );
    $elements[] = $this->wrapContainerWide($element);

    // Greetings.
    $elements[] = $this->buildGreetings($greetings);

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
   * Greetings.
   *
   * @param array $items
   *   The greetings items.
   *
   * @return array
   *   The render array.
   */
  private function buildGreetings(array $items): array {
    $current_user = $this->currentUser;
    $greetings = [];

    foreach ($items as $item) {
      // Skip if roles don't match.
      if ($item['roles'] && !array_intersect($item['roles'], $current_user->getRoles())) {
        continue;
      }

      $greetings[] = [
        '#theme' => 'gizra_assignment_greetings_item',
        '#message' => $item['message'],
        '#type' => $item['type'] ?? 'status',
      ];
    }

    $element = $this->wrapContainerWide($greetings) + [
      '#attached' => [
        'library' => ['gizra_assignment/greetings'],
      ],
    ];

    // Add cache context.
    $element['#cache']['contexts'][] = 'user.roles';

    return $element;
  }

  /**
   * Build the header.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `Group`).
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
