<?php

namespace Drupal\og_custom_view\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\og\OgAccessInterface;


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
    protected OgAccessInterface $og_access
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
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build, EntityInterface $entity, string $view_mode): array {
    if ($view_mode != 'full') {
      return $build;
    }
    if ($this->og_access->userAccess($entity, 'subscribe')) {
      $element = [
        '#type' => 'inline_template',
        '#template' => '<p> HI {{ name }}, click here to subscribe to {{ label }} </p>',
        '#context' => [
          'name' => $this->current_user->getDisplayName(),
          'label' => $entity->label(),
        ],
      ];

      $build[] = $element;
    }
    return $build;
  }

}
