<?php

namespace Drupal\server_general\ThemeTrait;

/**
 * Provides helper methods for rendering Person Cards elements.
 */
trait PersonCardThemeTrait {

  use ElementWrapThemeTrait;

  /**
   * Build a group of Person cards with a title and body.
   *
   * @param string $title
   *   The section title.
   * @param array $body
   *   A render array.
   * @param array $items
   *   An array of Person Cards render arrays.
   *
   * @return array
   *   A render array representing the full Person Cards section.
   */
  protected function buildElementPersonCards(string $title, array $body, array $items): array {
    return $this->buildElementLayoutTitleBodyAndItems(
      $title,
      $body,
      $this->buildCards($items),
    );
  }

  /**
   * Build a single Person card.
   *
   * @param string $image_url
   *   The URL of the profile image.
   * @param string $name
   *   The person’s title.
   * @param string $role
   *   The role of teh person.
   * @param string $label
   *   A label to display as a badge.
   * @param string|null $email
   *   The email address. If NULL, the email button will not have an href.
   * @param string|null $phone
   *   The phone number. If NULL, the call button will not have an href.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementPersonCard(string $image_url, string $name, string $role, string $label, ?string $email = NULL, ?string $phone = NULL): array {
    return [
      '#theme' => 'server_theme_element__person_cards',
      '#image_url' => $image_url,
      '#name' => $name,
      '#role' => $role,
      '#label' => $label,
      '#email' => $email,
      '#phone' => $phone,
    ];
  }

}
