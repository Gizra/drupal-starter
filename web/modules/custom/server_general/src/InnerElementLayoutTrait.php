<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper methods for rendering different Card layouts.
 *
 * A card layout can be for example a card with an image, or a card with
 * centered items. This trait should only be used by `InnerElementTrait`.
 * You should not try to call this trait's methods directly from the Style guide
 * or PEVB, instead you should be calling the methods from `InnerElementTrait`.
 *
 * Trait is providing helper methods for each card. One method equals one theme
 * file.
 */
trait InnerElementLayoutTrait {

  /**
   * Build "Card" layout - the simplest one.
   *
   * @param array $items
   *   The elements as render array.
   * @param string|null $bg_color
   *   Optional; The background color. Allowed values are:
   *   - 'light-gray'.
   *   - 'light-green'.
   *   - 'white'.
   *   If NULL, a transparent background will be added.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayout(array $items, string $bg_color = NULL): array {
    return [
      '#theme' => 'server_theme_inner_element_layout',
      '#items' => $this->wrapContainerVerticalSpacing($items),
      '#bg_color' => $bg_color,
    ];
  }

  /**
   * Build "Centered card" layout.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayoutCentered(array $items): array {
    return [
      '#theme' => 'server_theme_inner_element_layout__centered',
      '#items' => $this->wrapContainerVerticalSpacing($items, 'center'),
    ];
  }

  /**
   * Build "Centered card" layout.
   *
   * @param array $items
   *   The elements as render array.
   * @param array $bg_color
   *   Optional; The background color. Allowed values are:
   *    - 'light-gray'.
   *    - 'light-green'.
   *    - 'white'.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayoutCard(array $items, string $bg_color = NULL): array {
    return [
      '#theme' => 'server_theme_inner_element_layout__card',
      '#items' => $items,
      '#bg_color' => $bg_color,
    ];
  }

  /**
   * Build "Card with image" layout.
   *
   * This is the "base" helper method for rendering a card with image. Specific
   * cards may implement own helper methods, that will use this one.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $image
   *   The image render array.
   * @param array $items
   *   The rest of the items' render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayoutWithImage(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_inner_element_layout__with_image',
      '#image' => $image,
      '#url' => $url,
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

  /**
   * Build "Card with image horizontal" layout.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to link to.
   * @param array $image
   *   The image render array.
   * @param array $items
   *   The rest of the items' render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayoutWithImageHorizontal(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_inner_element_layout__with_image_horizontal',
      '#image' => $image,
      '#url' => $url,
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

}
