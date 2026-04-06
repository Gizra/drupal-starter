<?php

declare(strict_types=1);

namespace Drupal\server_ai_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Access check for the AI content generation route.
 */
class AiContentGenerateAccess implements AccessInterface {

  /**
   * Constructs an AiContentGenerateAccess handler.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Checks access for the AI content generation route.
   *
   * Requires both the 'generate ai content' permission and
   * the ability to create nodes of the specified type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeTypeInterface $node_type): AccessResultInterface {
    $has_permission = AccessResult::allowedIfHasPermission($account, 'generate ai content');
    $can_create = $this->entityTypeManager
      ->getAccessControlHandler('node')
      ->createAccess($node_type->id(), $account, [], TRUE);

    return $has_permission->andIf($can_create);
  }

}
