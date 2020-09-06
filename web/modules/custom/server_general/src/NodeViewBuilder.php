<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeViewBuilder as CoreNodeViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the core node view builder class to output nodes in custom style.
 */
class NodeViewBuilder extends CoreNodeViewBuilder {

  use EntityViewBuilderTrait;

  /**
   * The entity view builder service.
   *
   * @var \Drupal\server_general\EntityViewBuilder\EntityViewBuilderPluginManager
   */
  protected $entityViewBuilderPluginManager;

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $builder = parent::createInstance($container, $entity_type);
    $builder->entityViewBuilderPluginManager = $container->get('plugin.manager.server_general.entity_view_builder');

    return $builder;
  }

  /**
   * {@inheritDoc}
   *
   * This is a dispatcher method, that decides - according to the node type, to
   * which specific node type node vie builder service to call.
   *
   * @throws \Exception
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    return $this->doView($entity, $view_mode, $langcode);
  }

}
