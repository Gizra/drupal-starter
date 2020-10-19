<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\ComponentWrapTrait;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\TagBuilderTrait;

/**
 * An abstract class for Node View Builders classes.
 */
abstract class NodeViewBuilderAbstract extends EntityViewBuilderPluginAbstract {

  use ComponentWrapTrait;
  use ProcessedTextBuilderTrait;
  use TagBuilderTrait;

  /**
   * The image style to use on Hero images.
   */
  const IMAGE_STYLE_HERO = 'hero';

  /**
   * Default build in "Teaser" view mode.
   *
   * Show nodes as "cards".
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return mixed[]
   *   An array of elements for output on the page.
   */
  public function buildTeaser(array $build, NodeInterface $entity) {
    $build += $this->getElementBase($entity);
    $build['#theme'] = 'server_theme_card__simple';

    return $build;
  }

  /**
   * Get common elements for the view modes.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   A renderable array.
   */
  protected function getElementBase(NodeInterface $entity) {
    $element = [];
    // User may create a preview, so it won't have an ID or URL yet.
    $element['#nid'] = !$entity->isNew() ? $entity->id() : 0;
    $element['#url'] = !$entity->isNew() ? $entity->toUrl() : Url::fromRoute('<front>');
    $element['#title'] = $entity->label();

    return $element;
  }

  /**
   * Build the Hero Header section, with Title, and Background Image.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $image_field_name
   *   Optional; The field name. Defaults to "field_image".
   *
   * @return array
   *   A render array.
   */
  protected function buildHeroHeader(NodeInterface $entity, $image_field_name = 'field_image') {
    [$image] = $this->buildImage($entity, $image_field_name);

    $element = [
      '#theme' => 'server_theme_content__hero_header',
      '#title' => $entity->label(),
      '#background_image' => $image,
    ];

    return $this->wrapComponentWithContainer($element, 'hero-header-wrapper', 'fluid-container-full');
  }

  /**
   * Build the content tags section.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field_name
   *   Optional; The term reference field name. Defaults to "field_tags".
   *
   * @return array
   *   A render array.
   */
  protected function buildContentTags(NodeInterface $entity, $field_name = 'field_tags') {
    $tags = $this->buildTags($entity, $field_name);
    if (!$tags) {
      return [];
    }

    $element = [
      '#theme' => 'server_theme_content__tags',
      '#tags' => $tags,
    ];

    return $this->wrapComponentWithContainer($element, 'content-tags-wrapper', 'fluid-container-narrow');
  }

  /**
   * Build a list of tags.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field_name
   *   Optional; The term reference field name. Defaults to "field_tags".
   *
   * @return array
   *   A render array.
   */
  protected function buildTags(NodeInterface $entity, $field_name = 'field_tags') {
    if ($entity->{$field_name}->isEmpty()) {
      // No terms referenced.
      return [];
    }

    $tags = [];
    foreach ($entity->{$field_name}->referencedEntities() as $term) {
      $tags[] = $this->buildTag($term);
    }

    return $tags;
  }

  /**
   * Build an image referenced in the given entity's given field name.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field_name
   *   Optional; The field name. Defaults to "field_image".
   *
   * @return array
   *   An array containing url and alt.
   */
  protected function buildImage(NodeInterface $entity, $field_name = 'field_image') {
    if (empty($entity->{$field_name}) || $entity->get($field_name)->isEmpty()) {
      // No field, or it's empty.
      return [NULL, NULL];
    }
    $url = $this->entityTypeManager
      ->getStorage('image_style')
      ->load(self::IMAGE_STYLE_HERO)
      ->buildUrl($entity->get($field_name)[0]->entity->getFileUri());

    $alt = $entity->get($field_name)[0]->alt ?: '';
    return [$url, $alt];
  }

}
