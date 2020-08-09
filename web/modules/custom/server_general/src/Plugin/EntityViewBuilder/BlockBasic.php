<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\server_general\ComponentWrapTrait;
use Drupal\server_general\EntityViewBuilder\EntityViewBuilderPluginInterface;
use Drupal\server_general\ProcessedTextBuilderTrait;
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
  use ProcessedTextBuilderTrait;

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
    $build['title'] = $this->buildTitle($entity);
    dump($build['title']);
    $build['body'] = $this->buildBody($entity);
    $build['extra'] = $this->buildExtra();

    return $build;
  }

  /**
   * Get the Block's title.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  protected function buildTitle(EntityInterface $entity) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $entity->label(),
    ];

    return $this->wrapComponentWithContainer($element, 'title-wrapper');
  }

  /**
   * Get Extra info to add to the Block.
   *
   * @return array
   *   Render array.
   */
  protected function buildExtra() {
    $element = [
      '#markup' => $this->t('This is coming from \Drupal\server_general\Plugin\EntityViewBuilder\BlockBasic'),
    ];

    return $this->wrapComponentWithContainer($element, 'extra-wrapper');
  }

}
