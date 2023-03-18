<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;

/**
 * Helper methods for getting themed social share buttons.
 */
trait SocialShareTrait {

  /**
   * Build the social media buttons.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that's being shared.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildSocialShare(ContentEntityInterface $entity): array {
    // In preview state, since we don't have any URL for the entity yet.
    $url = $entity->isNew() ? '' : $entity->toUrl('canonical', ['absolute' => TRUE]);

    return $this->buildElementSocialShare(
      $entity->label(),
      $url,
    );
  }

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
      'twitter',
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
