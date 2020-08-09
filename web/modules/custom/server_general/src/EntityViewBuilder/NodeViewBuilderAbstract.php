<?php

namespace Drupal\server_general\EntityViewBuilder;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\ComponentWrapTrait;
use Drupal\server_general\TagBuilderTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeViewBuilderAbstract.
 */
abstract class NodeViewBuilderAbstract extends PluginBase implements EntityViewBuilderPluginInterface {

  use ComponentWrapTrait;
  use TagBuilderTrait;

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
   * Abstract constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager, AccountInterface $current_user, BlockManagerInterface $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build, EntityInterface $entity) {
    $bundle = $entity->bundle();
    $view_mode = $build['#view_mode'];

    // We should get a method name such as `buildFull`, and `buildTeaser`.
    $method = 'build' . mb_convert_case($view_mode, MB_CASE_TITLE);
    $method = str_replace(['_', '-', ' '], '', $method);

    if (!is_callable([$this, $method])) {
      throw new \Exception("The node view builder method `$method` for bundle $bundle and view mode $view_mode not found");
    }

    return $this->$method($build, $entity);

  }

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
   *   Optional; The field name. Defaults to "field_image".
   *
   * @return array
   *   A render array.
   */
  protected function buildHeroHeader(NodeInterface $entity, $image_field_name = 'field_image') {
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
    $tags = $this->buildTags($entity, $field_name);
    if (!$tags) {
      return [];
    }

    $element = [
      '#theme' => 'server_theme_content__tags',
      '#tags' => $tags,
    ];

    return $this->wrapComponentWithContainer($element, 'content-tags');
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

  /**
   * Build the body of node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "body".
   *
   * @return array
   *   Render array.
   */
  protected function buildBody(NodeInterface $entity, $field = 'body') {
    $element = $this->buildProcessedText($entity, $field);
    return $this->wrapComponentWithContainer($element, 'content-body');
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
      $options['type'] = 'text_summary_or_trimmed';
    }

    return [
      '#theme' => 'server_theme_content__body',
      '#content' => $entity->get($field)->view($options),
    ];
  }

}
