<?php

namespace Drupal\server_general\Routing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\LockedLandingPages;
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
   * The locked landing page service.
   *
   * @var \Drupal\server_general\LockedLandingPages
   */
  protected $lockedLPService;

  /**
   * Constructs a new LockedPagesRouteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   * @param \Drupal\server_general\LockedLandingPages $locked_lp_service
   *   The locked landing page service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, LockedLandingPages $locked_lp_service) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->lockedLPService = $locked_lp_service;
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
   * Checks if current node can be accessed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $node->getType() == 'landing_page') {
      $main_settings = $this->lockedLPService->getMainSettings();
      $cache_tags = $main_settings->getCacheTags();
      if ($this->lockedLPService->isNodeLocked($node)) {
        return AccessResult::forbidden()->addCacheableDependency($node)->addCacheTags($cache_tags);
      }
    }
    // Only allow deletion if user has permission to delete landing pages.
    if ($account->hasPermission('delete any landing page content')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
