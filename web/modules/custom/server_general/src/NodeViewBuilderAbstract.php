<?php

namespace Drupal\server_general;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;


/**
 * Class NodeViewBuilderPerBundleAbstract.
 */
class NodeViewBuilderAbstract {

  use ComponentWrapTrait;

  /**
   * The image style to use on Hero images.
   */
  const IMAGE_STYLE_HERO = 'hero';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * NodeViewBuilderCollection constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, AccountInterface $current_user, BlockManagerInterface $block_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->blockManager = $block_manager;
  }

  /**
   * Default build in "Card" view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return mixed[]
   *   An array of elements for output on the page.
   */
  public function buildCard(array $build, NodeInterface $entity) {
    $build += $this->getElementBase($entity);
    $build['#theme'] = 'server_theme_card';

    return $build;
  }

  /**
   * Get common elements for the view modes.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array[]
   *   A renderable array.
   */
  protected function getElementBase(NodeInterface $entity) {
    $element = [];
    $element['#nid'] = $entity->id();
    $element['#title'] = $entity->label();
    $element['#url'] = $entity->toUrl()->toString();

    return $element;
  }

  /**
   * Build the Hero Header section, with Title, and Background Image.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $image_field_name
   *   The field name to grab the image from.
   *
   * @return array
   *   A render array.
   */
  protected function buildHeroHeader(NodeInterface $entity, $image_field_name) {
    list($image) = $this->buildImage($entity, $image_field_name);

    $element = [
      '#theme' => 'server_theme_content__hero_header',
      '#title' => $entity->label(),
      '#background_image' => $image,
    ];

    return $this->wrapComponentWithContainer($element, 'hero-header', 'fluid-container-full');
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
    if ($entity->{$field_name}->isEmpty()) {
      // No terms referenced.
      return [];
    }

    $tags = [];
    foreach ($entity->{$field_name}->referencedEntities() as $term) {
      $tags[] = $this->getTag($entity);
    }

    $element = [
      '#theme' => 'server_theme_content__tags',
      '#tags' => $tags,
    ];

    return $this->wrapComponentWithContainer($element, 'content-tags');
  }



  /**
   * Build an image referenced in the given entity's given field name.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field_name
   *   Optional; The field name. Defaults to "field_image".
   *
   * @return array|string
   *   An array containing url and alt.
   */
  protected function buildImage(NodeInterface $entity, $field_name = 'field_image') {
    if ($entity->get($field_name)->isEmpty()) {
      return [NULL, NULL];
    }
    $url = $this->entityTypeManager
      ->getStorage('image_style')
      ->load(self::IMAGE_STYLE_HERO)
      ->buildUrl($entity->get($field_name)[0]->entity->getFileUri());

    $alt = $entity->get($field_name)[0]->alt ?: '';
    return [$url, $alt];
  }

  /**
   * Build a (processed) text of the content.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "body".
   * @param bool $summary_or_trimmed
   *   Optional; If TRUE then the "summary or trimmed" formatter will be used.
   *   Defaults to FALSE.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedText(NodeInterface $entity, $field = 'body', $summary_or_trimmed = FALSE) {
    if ($entity->get($field)->isEmpty()) {
      return [];
    }

    $options = ['label' => 'hidden'];

    if ($summary_or_trimmed) {
      $options['type'] = ['text_summary_or_trimmed'];
    }

    return [
      '#theme' => 'server_theme_content__body',
      '#content' => $entity->get($field)->view($options),
    ];
  }

}
