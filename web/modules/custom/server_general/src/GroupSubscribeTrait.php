<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Helper methods for getting a group subscribe text.
 */
trait GroupSubscribeTrait {

  /**
   * Get button.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $name
   *   The user name.
   *
   * @return array
   *   The rendered Group Subscribe Text array.
   */
  protected function buildGroupSubscribeText(ContentEntityInterface $entity, string $name): array {
    return [
      '#theme' => 'server_theme_group_subscribe',
      '#type' => 'subscribe',
      '#url' => $entity->get('og_group')->view('default')['0']['#url'],
      '#title' => $entity->get('og_group')->view('default')['0']['#title'],
      '#name' => $name,
      '#label' => $entity->label(),
    ];
  }

}
