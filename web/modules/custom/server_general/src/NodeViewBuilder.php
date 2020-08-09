<?php

namespace Drupal\server_general;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeViewBuilder as CoreNodeViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeViewBuilder.
 *
 * Overrides the core node view builder class to output nodes in custom style.
 */
class NodeViewBuilder extends CoreNodeViewBuilder {

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
  public function build(array $build) {
    $build = parent::build($build);

    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $build['#node'];
    $bundle = $entity->bundle();

    // Check if we have a plugin to take over the bundle of this entity.
    $plugin_id = $entity->getEntityTypeId() . '.' . $bundle;

    try {
      $plugin_definition = $this->entityViewBuilderPluginManager->getDefinition($plugin_id);
    }
    catch (PluginNotFoundException $e) {
      // We don't have a plugin.
      return $build;
    }

    $plugin = $this->entityViewBuilderPluginManager->createInstance($plugin_id);

    $view_mode = $build['#view_mode'];

    // We should get a method name such as `buildFull`, and `buildTeaser`.
    $method = 'build' . mb_convert_case($view_mode, MB_CASE_TITLE);
    $method = str_replace(['_', '-', ' '], '', $method);

    if (!is_callable([$plugin, $method])) {
      throw new \Exception("The node view builder method `$method` for bundle $bundle and view mode $view_mode not found");
    }

    // Remove the unneeded stuff from the default build.
    foreach (Element::children($build) as $key) {
      unset($build[$key]);
    }

    return $plugin->$method($build, $entity);
  }

}
