<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\server_general\GroupSubscribeTrait;
use Drupal\server_general\TitleAndLabelsTrait;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract implements EntityViewBuilderPluginInterface {

  use TitleAndLabelsTrait;
  use GroupSubscribeTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Node Group object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, OgAccessInterface $og_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user,);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('og.access')
    );
  }

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $elements = [];

    $element = $this->buildLabelsFromText([$entity->label()]);
    $elements[] = $this->wrapContainerWide($element);

    // Authenticated user subscribe link.
    $og_group_view = $entity->get('og_group')->view('default')['0'];
    if ($og_group_view['#type'] == 'link' &&  $og_group_view['#url']->getRouteName() == 'og.subscribe') {
      if ($this->currentUser->isAuthenticated()) {
        $user = $this->entityTypeManager->getStorage('user')->load(($this->currentUser->id()));
        $access = $this->ogAccess->userAccess($entity, 'subscribe', $user);
        if ($access->isAllowed()) {
          $element = $this->buildGroupSubscribeText($entity, $this->currentUser->getDisplayName());
          $elements[] = $this->wrapContainerWide($element);
        }
      }
    }
    else {
      // Default Behaviour.
      $element = $entity->get('og_group')->view('default');
      $elements[] = $this->wrapContainerWide($element);
    }
    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    $build[] = $this->wrapContainerBottomPadding($elements);
    return $build;
  }

}
