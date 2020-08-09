<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Component\Plugin\PluginInspectionInterface;
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
   *
   * @return array
   *   The new render array.
   */
  public function build(array $build);

}
