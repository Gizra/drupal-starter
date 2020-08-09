<?php

namespace Drupal\server_general;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;

/**
 * Trait EntityViewBuilderTrait.
 *
 * Helper method for dispatching the `view` function.
 */
trait EntityViewBuilderTrait {

  /**
   * Call View Builder plugin, if exists, to get the correct render array.
   *
   * This is a dispatcher method, that decides - according to the entity type,
   * and bundle to which plugin to call, in order to get a render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode. Defaults to "full".
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   Render array.
   */
  public function doView(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    $bundle = $entity->bundle();

    // Check if we have a plugin to take over the bundle of this entity.
    $plugin_id = $entity->getEntityTypeId() . '.' . $bundle;

    try {
      // Check if the plugin exists.
      $this->entityViewBuilderPluginManager->getDefinition($plugin_id);
    }
    catch (PluginNotFoundException $e) {
      // We don't have a plugin.
      return $build;
    }

    /** @var \Drupal\server_general\EntityViewBuilder\EntityViewBuilderPluginInterface $plugin */
    $plugin = $this->entityViewBuilderPluginManager->createInstance($plugin_id);

    // Remove the unneeded stuff from the default build. We would add everything
    // manually.
    foreach (Element::children($build) as $key) {
      unset($build[$key]);
    }

    return $plugin->build($build, $entity, $view_mode, $langcode);
  }

}
