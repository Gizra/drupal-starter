<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\server_general\ComponentWrapTrait;
use Drupal\server_general\EntityViewBuilder\EntityViewBuilderPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockBasic.
 *
 * @EntityViewBuilder(
 *   id = "block_content.basic",
 *   label = @Translation("Block content - Basic"),
 *   description = "Block content view builder for Basic bundle."
 * )
 */
class BlockBasic extends PluginBase implements EntityViewBuilderPluginInterface {

  use ComponentWrapTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build, EntityInterface $entity, $view_mode = 'full') {
    $build['extra'] = ['#markup' => $this->t('This is coming from \Drupal\server_general\Plugin\EntityViewBuilder\BlockBasic')];

    return $this->wrapComponentWithContainer($build, 'block-wrapper');
  }

}
