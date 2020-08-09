<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\block_content\BlockContentViewBuilder as CoreBlockContentViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockViewBuilder.
 *
 * Overrides the core block view builder class to output nodes in custom style.
 */
class BlockContentViewBuilder extends CoreBlockContentViewBuilder {

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
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    return $this->doView($entity, $view_mode, $langcode);
  }

}
