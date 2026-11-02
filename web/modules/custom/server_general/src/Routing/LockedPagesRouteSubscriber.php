<?php

namespace Drupal\server_general\Routing;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\LockedPages;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
final class LockedPagesRouteSubscriber extends RouteSubscriberBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The locked pages service.
   *
   * @var \Drupal\server_general\LockedPages
   */
  protected $lockedPagesService;

  /**
   * Constructs a new LockedPagesRouteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\server_general\LockedPages $locked_pages_service
   *   The locked pages service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, LockedPages $locked_pages_service) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->lockedPagesService = $locked_pages_service;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Check if the route exists and is for deleting a node.
    if ($route = $collection->get('entity.node.delete_form')) {
      $route->setRequirement('_custom_access', 'server_general.route_subscriber::access');
    }
  }

  /**
   * Checks if current node delete form can be accessed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node): AccessResultInterface {
    // Get the list of bundles that can be restricted.
    $bundles = $this->lockedPagesService->getReferencedBundles();
    $main_settings = $this->lockedPagesService->getMainSettings();
    $cache_tags = NULL;
    if ($main_settings instanceof ConfigPages) {
      $cache_tags = $main_settings->getCacheTags();
    }
    // If node is locked, we don't allow accessing the delete page at all.
    if (in_array($node->getType(), $bundles) && $this->lockedPagesService->isNodeLocked($node)) {
      $result = AccessResult::forbidden()->addCacheableDependency($node);
      if (!empty($cache_tags)) {
        $result->addCacheTags($cache_tags);
      }
      return $result;
    }

    // Check access by permission. If the user can delete any node of this
    // bundle, or if the user can delete own node of this bundle and is the
    // owner.
    $has_delete_permission = $account->hasPermission("delete any {$node->bundle()} content") || ($node->getOwnerId() === $account->id() && $account->hasPermission("delete own {$node->bundle()} content"));
    $result = AccessResult::allowedIf($has_delete_permission);
    $result->addCacheableDependency($node);
    if (!empty($cache_tags)) {
      $result->addCacheTags($cache_tags);
    }

    return $result;
  }

}
