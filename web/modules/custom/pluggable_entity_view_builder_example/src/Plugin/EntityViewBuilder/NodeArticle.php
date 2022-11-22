<?php

namespace Drupal\pluggable_entity_view_builder_example\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\pluggable_entity_view_builder_example\ElementContainerTrait;
use Drupal\pluggable_entity_view_builder_example\ProcessedTextBuilderTrait;
use Drupal\pluggable_entity_view_builder_example\TagBuilderTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Article" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Article"),
 *   description = "Node view builder for Article bundle."
 * )
 */
class NodeArticle extends EntityViewBuilderPluginAbstract {

  use ElementContainerTrait;
  use ProcessedTextBuilderTrait;
  use TagBuilderTrait;


    /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupSubscribeFormatter object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountInterface $current_user, OgAccessInterface $og_access, EntityTypeManagerInterface $entity_type_manager) {
    
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('current_user'),
      $container->get('og.access'),
      $container->get('entity_type.manager')
    );
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
  public function buildFull(array $build, NodeInterface $entity): array {


    $entity_type_id = $entity->getEntityTypeId();

    // Header.
    $build[] = ['#markup' => "This will be printed in the Article's full node view"];

    // Header.
    $build[] = $this->buildHeroHeader($entity);

    // Tags.
    $build[] = $this->buildContentTags($entity);

    // Body.
    $build[] = $this->buildProcessedText($entity);

    // If Paragraphs example module is enabled, show the paragraphs.
    if ($entity->hasField('field_paragraphs') && !$entity->field_paragraphs->isEmpty()) {
      $build[] = [
        '#theme' => 'pluggable_entity_view_builder_example_cards',
        '#items' => $this->buildReferencedEntities($entity->field_paragraphs, 'full'),
      ];
    }

    // Comments.
    $build[] = $this->buildComment($entity);

    // Load Tailwind CSS framework, so our example are styled.
    $build['#attached']['library'][] = 'pluggable_entity_view_builder_example/tailwind';

   // return $build;



    // Title.
    $build[] = [
      '#theme' => 'your_fancy_page_title',
      '#title' => $entity->label(),
    ];
    $user = $this->entityTypeManager->getStorage('user')->load(($this->currentUser->id()));
    /* var_dump($entity->getTitle());
    exit; */
    $storage = $this->entityTypeManager->getStorage('og_membership');
    $props = [
      'uid' => $user ? $user->id() : 0,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_bundle' => $entity->bundle(),
      'entity_id' => $entity->id(),
    ];

    $memberships = $storage->loadByProperties($props);
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = reset($memberships);
    if ($membership) {
      $cache_meta->merge(CacheableMetadata::createFromObject($membership));
      $cache_meta->applyTo($elements);
      if ($membership->isBlocked()) {
        // If user is blocked, they should not be able to apply for
        // membership.
        return $elements;
      }
      // Member is pending or active.
      $link['title'] = $this->t('Unsubscribe from group');
      $link['url'] = Url::fromRoute('og.unsubscribe', [
        'entity_type_id' => $entity_type_id,
        'group' => $entity->id(),
      ]);
      $link['class'] = ['unsubscribe'];
    }
    else {
      // If the user is authenticated, set up the subscribe link.
      if ($user->isAuthenticated() && ($access = $this->ogAccess->userAccess($entity, 'subscribe', $user)) && $access->isAllowed()) {
        $parameters = [
          'entity_type_id' => $entity->getEntityTypeId(),
          'group' => $entity->id(),
          'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
        ];

        $url = Url::fromRoute('og.subscribe', $parameters);
        $link['title'] = $this->t(' Hi @username, click here if you would like to subscribe to this group called @title', array('@username' => $user->get('name')->value, '@title' => $entity->getTitle()));
        $link['class'] = ['subscribe', 'request'];
        $link['url'] = $url;
      }
      else {
        //$cache_meta->setCacheContexts(['user.roles:anonymous']);
        // User is anonymous, link to user login and redirect back to here.
        $url = Url::fromRoute('user.login', []);
      }
     // $cache_meta->applyTo($elements);

      
    }
    if (!empty($link['title'])) {
      $link += [
        'options' => [
          'attributes' => [
            'title' => $link['title'],
            'class' => ['group'] + $link['class'],
          ],
        ],
      ];

      $elements[0] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => $link['url'],
      ];
    }
    $build[] = $elements;
  //$build['#attached']['library'][] = 'pluggable_entity_view_builder_example/tailwind';

   return $build;
    //return $elements;

/*  echo "<pre>";
var_dump($membership);
exit; */
    // Body field.
   // $build[] = $this->buildProcessedText($entity);


    // Header.
  //  $build[] = $this->buildHeroHeader($entity);

    // Tags.
   // $build[] = $this->buildContentTags($entity);

    // Body.
    //$build[] = $this->buildProcessedText($entity);

  

    // Comments.
    //$build[] = $this->buildComment($entity);

    // Load Tailwind CSS framework, so our example are styled.
    $build['#attached']['library'][] = 'pluggable_entity_view_builder_example/tailwind';

    return $build;
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
   * @return array
   *   Render array.
   */
  public function buildTeaser(array $build, NodeInterface $entity): array {
    $element = [];

    $element['#theme'] = 'pluggable_entity_view_builder_example_card';

    // User may create a preview, so it won't have an ID or URL yet.
    $element['#url'] = !$entity->isNew() ? $entity->toUrl() : Url::fromRoute('<front>');
    $element['#title'] = $entity->label();
    $element['#body'] = $this->buildProcessedText($entity, 'body', TRUE);
    $element['#tags'] = $this->buildTags($entity);

    // Image as css image background.
    $image_info = $this->getImageAndAlt($entity, 'field_image');
    if ($image_info) {
      $element['#image'] = $image_info['url'];
      $element['#image_alt'] = $image_info['alt'];
    }

    $build[] = $element;

    // Load Tailwind CSS framework, so our example are styled nicer.
    $build['#attached']['library'][] = 'pluggable_entity_view_builder_example/tailwind';

    return $build;
  }

  /**
   * Get common elements for the view modes.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
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
   *   Render array.
   */
  protected function buildHeroHeader(NodeInterface $entity, $image_field_name = 'field_image'): array {
    $image_info = $this->getImageAndAlt($entity, $image_field_name);

    $element = [
      '#theme' => 'pluggable_entity_view_builder_example_hero_header',
      '#title' => $entity->label(),
      '#background_image' => !empty($image_info['url']) ? $image_info['url'] : '',
    ];

    return $this->wrapElementWithContainer($element);
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
   *   Render array.
   */
  protected function buildContentTags(NodeInterface $entity, string $field_name = 'field_tags'): array {
    $tags = $this->buildTags($entity, $field_name);
    if (!$tags) {
      return [];
    }

    return [
      '#theme' => 'pluggable_entity_view_builder_example_tags',
      '#tags' => $tags,
    ];
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
   *   Render array.
   */
  protected function buildTags(NodeInterface $entity, string $field_name = 'field_tags'): array {
    if (empty($entity->{$field_name}) || $entity->{$field_name}->isEmpty()) {
      // No terms referenced.
      return [];
    }

    $tags = [];
    foreach ($entity->{$field_name}->referencedEntities() as $term) {
      $tags[] = $this->buildTag($term);
    }

    return $tags;
  }

}
