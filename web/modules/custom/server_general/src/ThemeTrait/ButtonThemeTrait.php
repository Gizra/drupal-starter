<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper methods for getting a themed button.
 */
trait ButtonThemeTrait {

  use BuildFieldTrait;

  /**
   * Build a button.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The button's title.
   * @param \Drupal\Core\Url $url
   *   The button's URL as Url object.
   * @param string $button_type
   *   Type of button. Acceptable values: 'primary', 'secondary', 'tertiary'.
   *   Defaults to 'primary'.
   * @param string|null $icon
   *   The name of the icon to add as prefix. Allowed values are:
   *   - `download`.
   *   If NULL, no icon would be added. Defaults to NULL.
   * @param bool $open_new_tab
   *   Whether the button should open in a new tab, defaults to FALSE.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButton(array|string|TranslatableMarkup $title, Url $url, string $button_type = 'primary', ?string $icon = NULL, bool $open_new_tab = FALSE): array {
    $button_types = ['primary', 'secondary', 'tertiary'];
    if (!in_array($button_type, $button_types)) {
      $button_type = 'primary';
    }
    return [
      '#theme' => 'server_theme_button',
      '#url' => $url,
      '#title'  => $title,
      '#button_type' => $button_type,
      '#icon' => $icon,
      '#open_new_tab' => $open_new_tab,
    ];
  }

}
