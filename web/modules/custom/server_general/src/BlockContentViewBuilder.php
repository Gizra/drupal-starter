<?php

namespace Drupal\server_general;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\Element;
use Drupal\block_content\BlockContentViewBuilder as CoreBlockContentViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockViewBuilder.
 *
 * Overrides the core block view builder class to output nodes in custom style.
 */
class BlockContentViewBuilder extends CoreBlockContentViewBuilder {

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

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    $bundle = $entity->bundle();

    // Check if we have a plugin to take over the bundle of this entity.
    $plugin_id = $entity->getEntityTypeId() . '.' . $bundle;

    try {
      // Check if plugin exists.
      $this->entityViewBuilderPluginManager->getDefinition($plugin_id);
    }
    catch (PluginNotFoundException $e) {
      // We don't have a plugin.
      return $build;
    }

    $plugin = $this->entityViewBuilderPluginManager->createInstance($plugin_id);

    // Remove the unneeded stuff from the default build. We would add everything
    // manually.
    foreach (Element::children($build) as $key) {
      unset($build[$key]);
    }

    return $plugin->build($build, $entity);
  }

}
