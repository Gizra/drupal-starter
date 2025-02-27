<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * An abstract class for Node View Builders classes.
 */
abstract class NodeViewBuilderAbstract extends EntityViewBuilderPluginAbstract {

  use ElementWrapThemeTrait;
  use ProcessedTextBuilderTrait;

}
