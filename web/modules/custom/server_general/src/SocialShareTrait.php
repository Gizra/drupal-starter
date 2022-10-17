<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;

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
   *   A render-able social media element.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildSocialShare(ContentEntityInterface $entity): array {
    // In preview state, since we don't have any URL for the entity yet.
    $url = $entity->isNew() ? '' : $entity->toUrl('canonical', ['absolute' => TRUE])->toString();

    $items = [];
    $services = [
      'twitter',
      'linkedin',
      'facebook',
      'email',
    ];
    foreach ($services as $service) {
      $items[] = [
        '#theme' => 'server_theme_social_share_button',
        '#url' => $url,
        '#service' => $service,
        '#email_subject' => $entity->label(),
      ];
    }

    return [
      '#theme' => 'server_theme_social_share_buttons',
      '#items' => $items,
    ];
  }

}
