<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Url;

/**
 * Helper methods for getting themed social share buttons.
 */
trait SocialShareThemeTrait {

  /**
   * Build the social media buttons element.
   *
   * @param string $title
   *   The email subject for the "Email" service.
   * @param \Drupal\Core\Url $url
   *   The URL of the entity.
   *
   * @return array
   *   The render array.
   */
  protected function buildElementSocialShare(string $title, Url $url): array {
    $items = [];
    $services = [
      'x',
      'linkedin',
      'facebook',
      'email',
    ];
    foreach ($services as $service) {
      $item = [
        '#theme' => 'server_theme_social_share_button',
        '#url' => $url,
        '#service' => $service,
        '#email_subject' => $title,
      ];

      if ($service === 'email') {
        $item['#email_subject'] = $title;
      }
      $items[] = $item;
    }

    return [
      '#theme' => 'server_theme_social_share_buttons',
      '#items' => $items,
    ];
  }

}
