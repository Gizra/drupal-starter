<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Class EntityViewBuilderPluginInterface.
 */
interface EntityViewBuilderPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  public function build(array $build);

}
