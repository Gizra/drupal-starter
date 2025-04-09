<?php

namespace Drupal\og_custom_view\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\og_custom_view\GroupProxy;
use Drupal\og_custom_view\GroupInterface;


/**
 * The "Node News" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends PluginBase implements EntityViewBuilderPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, 
    protected EntityTypeManagerInterface $entity_type_manager, 
    protected AccountInterface $current_user,
    protected GroupInterface $group_proxy 
  ) {
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
      $container->get('class_resolver')->getInstanceFromDefinition(GroupProxy::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build, EntityInterface $group, string $view_mode): array {
    if ($view_mode != 'full') {
      return $build;
    }
    $messages = [
      'subscribe' =>
        '<p> HI {{ name }}, click {{ link("here", url) }} to subscribe to {{ label }} </p>'
    ];
    $build[] = $this->group_proxy->userGreeting($group, $this->current_user, $messages);
    return $build;
  }

}
