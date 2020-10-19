<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\pluggable_entity_view_builder\ComponentWrapTrait;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;

/**
 * The "Block Basic" plugin.
 *
 * @EntityViewBuilder(
 *   id = "block_content.basic",
 *   label = @Translation("Block content - Basic"),
 *   description = "Block content view builder for Basic bundle."
 * )
 */
class BlockBasic extends EntityViewBuilderPluginAbstract {

  use ComponentWrapTrait;
  use ProcessedTextBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function buildFull(array $build, EntityInterface $entity) {
    $build['title'] = $this->buildTitle($entity);
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

    return $this->wrapComponentWithContainer($element, 'title-wrapper', 'fluid-container-narrow');
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

    return $this->wrapComponentWithContainer($element, 'extra-wrapper', 'fluid-container-narrow');
  }

}
