<?php

declare(strict_types=1);


namespace Drupal\server_general;

use Drupal\Core\Url;

/**
 * Helper methods for rendering different Card layouts.
 *
 * A card layout can be for example a card with an image, or a card with
 * centered items. This trait should only be used by `CardTrait`. You should not
 * try to call this trait's methods directly from the Style guide or PEVB,
 * instead you should be calling the methods from `CardTrait`.
 *
 * Trait is providing helper methods for each card. One method equals one theme
 * file.
 */
trait CardLayoutTrait {

  /**
   * Build "Card" layout - the simplest one.
   *
   * @param array $items
   *   The elements as render array.
   *
   * @return array
   *   Render array.
   */
  protected function buildCardLayout(array $items): array {
    return [
      '#theme' => 'server_theme_card_layout',
      '#items' => $this->wrapContainerVerticalSpacing($items),
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
  protected function buildCardLayoutCentered(array $items): array {
    return [
      '#theme' => 'server_theme_card_layout__centered',
      '#items' => $this->wrapContainerVerticalSpacing($items, 'center'),
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
  protected function buildCardLayoutWithImage(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_card_layout__with_image',
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
  protected function buildCardLayoutWithImageHorizontal(Url $url, array $image, array $items): array {
    return [
      '#theme' => 'server_theme_card_layout__with_image_horizontal',
      '#image' => $image,
      '#url' => $url,
      '#items' => $this->wrapContainerVerticalSpacing($items),
    ];
  }

}
