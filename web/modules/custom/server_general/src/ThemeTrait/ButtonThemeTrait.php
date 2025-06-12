<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper methods for getting a themed button.
 */
trait ButtonThemeTrait {

  use BuildFieldTrait;

  /**
   * Build a Primary button.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButtonPrimary(Link $link): array {
    return $this->buildButtonHelper($link, ButtonTypeEnum::Primary);
  }

  /**
   * Build a Primary button that opens in a new tab, if link is external.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButtonPrimaryOpenInNewTabOnExternalLink(Link $link): array {
    return $this->buildButtonHelper($link, ButtonTypeEnum::Primary, $link->getUrl()->isExternal());
  }

  /**
   * Build a Secondary button.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButtonSecondary(Link $link): array {
    return $this->buildButtonHelper($link, ButtonTypeEnum::Secondary);
  }

  /**
   * Build a Tertiary button.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButtonTertiary(Link $link): array {
    return $this->buildButtonHelper($link, ButtonTypeEnum::Tertiary);
  }

  /**
   * Build a Download button that opens in a new tab.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButtonDownload(Link $link): array {
    return $this->buildButtonHelper($link, ButtonTypeEnum::Secondary, TRUE, ButtonIconEnum::Download);
  }

  /**
   * Build a button.
   *
   * @param \Drupal\Core\Link $link
   *   The link object..
   * @param \Drupal\server_general\ThemeTrait\ButtonTypeEnum $button_type
   *   Type of button.
   * @param bool $open_new_tab
   *   Whether the button should open in a new tab, defaults to FALSE.
   * @param \Drupal\server_general\ThemeTrait\ButtonIconEnum $icon
   *   The name of the icon to add as prefix.
   *
   * @return array
   *   The rendered button array.
   */
  private function buildButtonHelper(Link $link, ButtonTypeEnum $button_type = ButtonTypeEnum::Primary, bool $open_new_tab = FALSE, ButtonIconEnum $icon = ButtonIconEnum::NoIcon): array {
    return [
      '#theme' => 'server_theme_button',
      '#url' => $link->getUrl(),
      '#title'  => $link->getText(),
      '#button_type' => $button_type->value,
      '#icon' => $icon->value,
      '#open_new_tab' => $open_new_tab,
    ];
  }

}
