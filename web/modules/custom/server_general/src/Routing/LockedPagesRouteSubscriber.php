<?php

namespace Drupal\server_general\Routing;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
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
   * Constructs a new LockedPagesRouteSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Check if the route exists and is for deleting a node.
    if ($route = $collection->get('entity.node.delete_form')) {
      $route->setRequirement('_custom_access', 'Drupal\server_general\Routing\LockedPagesRouteSubscriber::access');
    }
  }

  /**
   * Returns the 'main_settings' config page.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The 'main_settings' config page entity or null if not found.
   */
  protected function getMainSettings() {
    /** @var \Drupal\config_pages\ConfigPagesStorage $config_pages_storage */
    $config_pages_storage = $this->entityTypeManager->getStorage('config_pages');
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $main_settings */
    $main_settings = $config_pages_storage->load('main_settings');
    return $main_settings;
  }

  /**
   * Returns list of restricted node ids from config pages.
   *
   * @return array
   *   List of ids.
   */
  protected function getRestrictedNodes() {
    $main_settings = $this->getMainSettings();

    if (!$main_settings instanceof ConfigPages) {
      return [];
    }

    $locked_nodes = $main_settings->get('field_locked_landing_pages')->getValue();

    return array_column($locked_nodes, 'target_id');
  }

  /**
   * Checks if current node is locked.
   *
   * @return bool
   *   Returns TRUE if node is locked.
   */
  public function isNodeLocked(NodeInterface $node) {
    $restricted_nodes = $this->getRestrictedNodes();

    return in_array($node->id(), $restricted_nodes);
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
      $main_settings = $this->getMainSettings();
      $cache_tags = $main_settings->getCacheTags();
      if (!$this->isNodeLocked($node)) {
        return AccessResult::forbidden()->addCacheableDependency($node)->addCacheTags($cache_tags);
      }
    }
    return AccessResult::allowed();
  }

}
