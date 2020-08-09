<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Class EntityViewBuilderPluginInterface.
 */
interface EntityViewBuilderPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Build a render array.
   *
   * @param array $build
   *   The existing render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to show.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The new render array.
   */
  public function build(array $build, EntityInterface $entity, $view_mode = 'full');

}
