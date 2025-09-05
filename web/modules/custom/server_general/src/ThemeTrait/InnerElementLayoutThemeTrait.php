<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;
use Drupal\server_general\ThemeTrait\Enum\AlignmentEnum;
use Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum;

/**
 * Helper methods for rendering different "inner element" layouts such as cards.
 *
 * An inner element can be for example a card with an image, or a search result
 * with centered items. This trait should only be used by other traits in
 * ThemeTrait namespace.
 * You should not try to call this trait's methods directly from the Style guide
 * or PEVB, instead you should be calling the methods from a custom
 * ThemeTrait such as Drupal\server_general\ThemeTrait\InfoCardThemeTrait.
 *
 * @see \Drupal\server_general\ThemeTrait\InfoCardThemeTrait::buildElementInfoCard.
 */
trait InnerElementLayoutThemeTrait {

  /**
   * Build "Card" layout - the simplest one.
   *
   * @param array $items
   *   The elements as render array.
   * @param \Drupal\server_general\ThemeTrait\Enum\BackgroundColorEnum $bg_color
   *   The background color.
   *
   * @return array
   *   Render array.
   */
  protected function buildInnerElementLayout(array $items, BackgroundColorEnum $bg_color = BackgroundColorEnum::Transparent): array {
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
      '#items' => $this->wrapContainerVerticalSpacing($items, AlignmentEnum::Center),
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
