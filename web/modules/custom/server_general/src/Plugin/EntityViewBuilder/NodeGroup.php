<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\ElementNodeGroupThemeTrait;
use Drupal\server_general\ThemeTrait\LinkThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {

  use ElementLayoutThemeTrait;
  use ElementNodeGroupThemeTrait;
  use EntityDateTrait;
  use LinkThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * The OG access service.
   */
  protected OgAccessInterface $ogAccess;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->ogAccess = $container->get('og.access');
    return $plugin;
  }

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    // The node's label.
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    $label = $node_type->label();

    $element = $this->buildElementNodeGroup(
      $entity->label(),
      $label,
      $this->getFieldOrCreatedTimestamp($entity, 'created'),
      $entity,
      $this->currentUser,
      $this->ogAccess,
      $this->buildProcessedText($entity, 'field_body')
    );

    $build[] = $element;
    return $build;
  }

}
