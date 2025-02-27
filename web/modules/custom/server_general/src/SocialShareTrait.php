<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\server_general\ThemeTrait\SocialShareThemeTrait;

/**
 * Helper methods for getting themed social share buttons.
 */
trait SocialShareTrait {

  use SocialShareThemeTrait;

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

}
