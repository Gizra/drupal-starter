<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
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

  /**
   * The responsive image style to use on Hero images.
   */
  const RESPONSIVE_IMAGE_STYLE_HERO = 'hero';

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
  protected function getElementBase(NodeInterface $entity): array {
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
  protected function buildHeroHeader(NodeInterface $entity, string $image_field_name = 'field_image'): array {
    return [
      '#theme' => 'server_theme_content__hero_header',
      '#title' => $entity->label(),
      '#image' => $this->buildMediaResponsiveImage($entity, $image_field_name, self::RESPONSIVE_IMAGE_STYLE_HERO),
    ];
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
  protected function buildContentTags(NodeInterface $entity, string $field_name = 'field_tags'): array {
    $tags = $this->buildTags($entity, $field_name);
    if (!$tags) {
      return [];
    }

    $element = [
      '#theme' => 'server_theme_content__tags',
      '#tags' => $tags,
    ];

    return $this->wrapElementWideContainer($element);
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
  protected function buildTags(NodeInterface $entity, string $field_name = 'field_tags'): array {
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
   * Build the page title and hide it if it's set to be hidden.
   *
   * The decision whether to hide it or not depends on the value of
   * field_is_title_hidden field on the entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity that's being built.
   *
   * @return array
   *   A renderable array of the page title.
   */
  protected function buildConditionalPageTitle(NodeInterface $entity): array {
    if ($entity->hasField('field_is_title_hidden') && $this->getBooleanFieldValue($entity, 'field_is_title_hidden')) {
      // Title should be hidden.
      return [];
    }

    return [
      '#theme' => 'server_theme_page_title',
      '#title' => $entity->label(),
    ];
  }

}
