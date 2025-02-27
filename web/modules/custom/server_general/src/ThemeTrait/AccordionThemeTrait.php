<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Helpers to build an accordion.
 */
trait AccordionThemeTrait {

  use LineSeparatorThemeTrait;

  /**
   * Build an Accordion.
   *
   * @param array $items
   *   Items rendered with `AccordionThemeTrait::buildElementAccordionItem`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementAccordion(array $items): array {
    // Add line separators to items.
    $items_wrapped = [];
    foreach ($items as $item) {
      $items_wrapped[] = $this->buildLineSeparator();
      $items_wrapped[] = $item;
    }

    // Accordion.
    return [
      '#theme' => 'server_theme_element__accordion',
      '#items' => $items_wrapped,
    ];
  }

  /**
   * Build an accordion item.
   *
   * @param string|\Stringable $title
   *   The title.
   * @param array $description
   *   The description render array.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementAccordionItem(string|\Stringable $title, array $description): array {
    return [
      '#theme' => 'server_theme_element__accordion_item',
      '#title' => $title,
      '#description' => $description,
    ];
  }

}
