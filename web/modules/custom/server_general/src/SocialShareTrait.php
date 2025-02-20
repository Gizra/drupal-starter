<?php

namespace Drupal\server_general;

use Drupal\Core\Url;


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
  protected function buildSocialShare(string $title, Url $url): array {
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
