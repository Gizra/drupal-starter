<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\TagBuilderTrait;

/**
 * An abstract class for Node View Builders classes.
 */
abstract class NodeViewBuilderAbstract extends EntityViewBuilderPluginAbstract {

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;
  use TagBuilderTrait;

}
