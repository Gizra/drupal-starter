<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Link;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ThemeTrait\Enum\ButtonTypeEnum;

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
    return $this->buildButtonHelper($link, ButtonTypeEnum::Download);
  }

  /**
   * Build a button.
   *
   * @param \Drupal\Core\Link $link
   *   The link object.
   * @param \Drupal\server_general\ThemeTrait\Enum\ButtonTypeEnum $button_type
   *   Type of button.
   *
   * @return array
   *   The rendered button array.
   */
  private function buildButtonHelper(Link $link, ButtonTypeEnum $button_type = ButtonTypeEnum::Primary): array {
    $url = $link->getUrl();
    // toString(TRUE) collects cache metadata (contexts/tags) that must be
    // bubbled since we are passing a string prop rather than a URL object.
    $generated_url = $url->toString(TRUE);

    return [
      '#type' => 'component',
      '#component' => 'server_general:button',
      '#props' => [
        'button_type' => $button_type->value,
        'url' => $generated_url->getGeneratedUrl(),
        'title' => $link->getText(),
        'open_new_tab' => $url->isExternal(),
      ],
      '#cache' => [
        'contexts' => $generated_url->getCacheContexts(),
        'tags' => $generated_url->getCacheTags(),
        'max-age' => $generated_url->getCacheMaxAge(),
      ],
    ];
  }

}
