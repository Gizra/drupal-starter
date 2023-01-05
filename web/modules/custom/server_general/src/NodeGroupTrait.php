<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\OgMembershipInterface;


/**
 * Trait NodeGroupTrait - helper for building OG group field.
 *
 * @package Drupal\server_general
 */
trait NodeGroupTrait {

  /**
   * @param \Drupal\node\NodeInterface $entity
   *   Node entity.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user account.
   * @param bool $overridegit config --global user.email mgurjanov@gmail.com
   *   Flag that enables output override if TRUE.
   * @param string $field_name
   *   Computed OG group field name.
   *
   * @return array
   *   Rendered array.
   */
  public function buildOgGroupField(NodeInterface $entity, AccountInterface $currentUser, bool $override, string $field_name = 'og_group'): array {

    // If override is enabled we present user with different OG group field message.
    if ($override) {

      // Get OG group subscribe route.
      $parameters = [
        'entity_type_id' => $entity->getEntityTypeId(),
        'group' => $entity->id(),
        'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
      ];
      $url = Url::fromRoute('og.subscribe', $parameters);

      $og_group_field = [
        '#theme' => 'server_theme_node_group__og_group_field',
        '#title' => $this->t('Hi @name, click here if you would like to subscribe to this group called @label', ['@name' => $currentUser->getDisplayName(), '@label' => $entity->getTitle()]),
        '#link' => $url ?? '',
      ];
    }
    else {
      // We present user with default OG group field rendered output.
      // Check: web/modules/contrib/og/src/Plugin/Field/FieldFormatter/GroupSubscribeFormatter.php.
      $og_group_field_renderable_array = $entity->get($field_name)->view();
      $og_group_field = !empty($og_group_field_renderable_array) ? $og_group_field_renderable_array : [];
    }

    return $og_group_field;
  }

}
