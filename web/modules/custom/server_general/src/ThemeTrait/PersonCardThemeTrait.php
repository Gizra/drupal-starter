<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Helper methods for rendering Hero elements.
 */
trait PersonCardThemeTrait {

  // Use ButtonThemeTrait;.
  use ElementWrapThemeTrait;

  /**
   * Build Person cards.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param array $items
   *   The render array built with
   *   `ElementLayoutThemeTrait::buildElementLayoutTitleBodyAndItems`.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementPersonCards(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Build a Person card.
   *
   * @param string $image_url
   *   The URL of the image.
   * @param string $title
   *   The title.
   * @param string $subtitle
   *   The subtitle.
   * @param string|null $email
   *   The email address.
   *   If NULL, href will be empty. Defaults to NULL.
   * @param string|null $phone
   *   The phone number.
   *   If NULL, href will be empty. Defaults to NULL.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementPersonCard(string $image_url, string $title, string $subtitle, ?string $email = NULL, ?string $phone = NULL): array {
    return [
      '#theme' => 'server_theme_element__person_card',
      '#image_url' => $image_url,
      '#title' => $title,
      '#subtitle' => $subtitle,
      '#email' => $email,
      '#phone' => $phone,
    ];
  }

}
