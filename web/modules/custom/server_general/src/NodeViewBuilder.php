<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\Element;
use Drupal\node\NodeViewBuilder as CoreNodeViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeViewBuilder.
 *
 * Overrides the core node view builder class to output nodes in custom style.
 */
class NodeViewBuilder extends CoreNodeViewBuilder {

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $builder = parent::createInstance($container, $entity_type);

    return $builder;
  }

  /**
   * {@inheritDoc}
   *
   * This is a dispatcher method, that decides - according to the node type, to
   * which specific node type node vie builder service to call.
   *
   * @throws \Exception
   */
  public function build(array $build) {
    $build = parent::build($build);

    /** @var \Drupal\node\NodeInterface $entity */
    // Note that the $build array that arrive from
    // \Drupal\server_general\NodeViewBuilderTrait::buildRelatedItems
    // is missing some keys including the #entity_type.
    // We're fixing it by hardcoding `$build['#node']`, but need to see if it's
    // not hiding a different problem. For now it seems to work.
    $entity = $build['#node'];

    if (!in_array($entity->getType(), [
      'article',
      'page',
    ])) {
      // Not a node type we override.
      return $build;
    }

    $bundle = $entity->bundle();
    $builder_service = NULL;
    switch ($bundle) {
      case 'article':
        $builder_service = $this->nodeViewBuilderArticle;
        break;

      case 'page':
        $builder_service = $this->nodeViewBuilderBasicPage;
        break;
    }

    $view_mode = $build['#view_mode'];

    // We should get a method name such as `buildFull`, and `buildTeaser`.
    $method = 'build' . mb_convert_case($view_mode, MB_CASE_TITLE);
    $method = str_replace(['_', '-', ' '], '', $method);

    if (!is_callable([$builder_service, $method])) {
      throw new \Exception("The node view builder method `$method` for bundle $bundle and view mode $view_mode not found");
    }

    // Remove the unneeded stuff from the default build.
    foreach (Element::children($build) as $key) {
      unset($build[$key]);
    }

    return $builder_service->$method($build, $entity);
  }

}
