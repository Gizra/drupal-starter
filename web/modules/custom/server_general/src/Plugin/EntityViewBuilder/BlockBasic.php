<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
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

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function buildFull(array $build, FieldableEntityInterface $entity): array {
    // Title.
    $build[] = $this->buildTitle($entity);

    // Body.
    $build[] = $this->buildBody($entity);

    // Extra.
    $build[] = $this->buildExtra();

    return $build;
  }

  /**
   * Get the Block's title.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  protected function buildTitle(FieldableEntityInterface $entity): array {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $entity->label(),
    ];

    return $this->wrapElementWideContainer($element);
  }

  /**
   * Get Extra info to add to the Block.
   *
   * @return array
   *   Render array.
   */
  protected function buildExtra(): array {
    $element = [
      '#markup' => $this->t('This is coming from \Drupal\server_general\Plugin\EntityViewBuilder\BlockBasic'),
    ];

    return $this->wrapElementWideContainer($element);
  }

}
