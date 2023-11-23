<?php

declare(strict_types=1);

namespace Drupal\server_general;

/**
 * Helpers to create a list.
 */
trait ListTrait {

  /**
   * Build an unordered list.
   *
   * @param array $items
   *   The items of the list.
   *
   * @return array
   *   The render array of the list.
   */
  protected function buildElementUnorderedList(array $items): array {
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ];
  }

  /**
   * Build an ordered list.
   *
   * @param array $items
   *   The items of the list.
   *
   * @return array
   *   The render array of the list.
   */
  protected function buildElementOrderedList(array $items): array {
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => $items,
    ];
  }

}
