<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Component\Serialization\Json;

/**
 * Helpers to build a tooltip.
 */
trait TooltipTrait {

  use ElementTrait;

  /**
   * Build a span with a tooltip. This trait depends on the tooltip module.
   *
   * @param string $text
   *   The text to generate the link with the tooltip.
   * @param array $content
   *   Items to render inside the tooltip.
   * @param array $settings
   *   Additional settings to pass to render the tooltip.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementSpanWithTooltip(string|\Stringable $text, array $content, array $settings = []): array {
    $default_settings = [];
    $default_settings['placement'] = 'bottom';
    $default_settings['content'] = $this->renderer->render($content);
    $settings = array_merge($default_settings, $settings);

    // Create the tooltip element now.
    return [
      '#theme' => 'server_theme_element__tooltip_span',
      '#text' => $text,
      '#settings' => json::encode($settings),
    ];
  }

  /**
   * Build a link with a tooltip. This trait depends on the tooltip module.
   *
   * @param string $text
   *   The text to generate the link with the tooltip.
   * @param string $href
   *   The href of the link.
   * @param array $content
   *   Items to render inside the tooltip.
   * @param array $settings
   *   Additional settings to pass to render the tooltip.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementLinkWithTooltip(string|\Stringable $text, string $href, array $content, array $settings = []): array {
    $default_settings = [];
    $default_settings['placement'] = 'bottom';
    $default_settings['content'] = $this->renderer->render($content);
    $settings = array_merge($default_settings, $settings);

    // Create the tooltip element now.
    return [
      '#theme' => 'server_theme_element__tooltip_link',
      '#href' => $href,
      '#text' => $text,
      '#settings' => Json::encode($settings),
    ];
  }

}
