<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {

  use RedirectDestinationTrait;

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
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new nodegroup object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager service.
   */
  public function __construct(AccountInterface $current_user, OgAccessInterface $og_access, EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager) {
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('current_user'),
      $container->get('og.access'),
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
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
    $cache_meta = CacheableMetadata::createFromRenderArray($build);
    $cache_meta->addCacheContexts([
      'og_membership_state',
      'user.roles:authenticated',
    ]);
    $cache_meta->merge(CacheableMetadata::createFromObject($entity));

    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($this->currentUser->id());

    // User entity couldn't be loaded.
    if (!$user) {
      return $build;
    }

    // If the user is the group manager.
    if ($entity->getOwnerId() == $user->id()) {
      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'title' => $this->t('You are the group manager'),
          'class' => ['group', 'manager'],
        ],
        '#value' => $this->t('You are the group manager'),
      ];
      $cache_meta->applyTo($build);
      return $build;
    }

    // Get the OG membership.
    $membership = $this->membershipManager->getMembership($entity, $user->id(), []);
    if ($membership) {
      $cache_meta->merge(CacheableMetadata::createFromObject($membership));

      if ($membership->isBlocked()) {
        // Blocked users can't subscribe or unsubscribe.
        $cache_meta->applyTo($build);
        return $build;
      }

      // Show unsubscribe link.
      $link = [
        'title' => $this->t('Unsubscribe from group'),
        'url' => Url::fromRoute('og.unsubscribe', [
          'entity_type_id' => $entity->getEntityTypeId(),
          'group' => $entity->id(),
        ]),
        'class' => ['custom-unsubscribe'],
      ];
    }
    else {
      // Authenticated user: prepare subscribe URL.
      if ($user->isAuthenticated()) {
        $url = Url::fromRoute('og.subscribe', [
          'entity_type_id' => $entity->getEntityTypeId(),
          'group' => $entity->id(),
          'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
        ]);

        $title = $this->t(
          'Hi @username, click here if you would like to subscribe to this group called @title',
          [
            '@username' => $user->getDisplayName(),
            '@title' => $entity->label(),
          ]
        );
      }
      else {
        // Anonymous: login redirect.
        $url = Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
        $title = $this->t('Login to subscribe');
      }

      // Check OG access for subscription.
      $access = $this->ogAccess->userAccess($entity, 'subscribe without approval', $user);
      if ($access->isAllowed()) {
        $link = [
          'title' => $title,
          'url' => $url,
          'class' => ['custom-subscribe'],
        ];
      }
      elseif (($access = $this->ogAccess->userAccess($entity, 'subscribe', $user)) && $access->isAllowed()) {
        $link = [
          'title' => $title,
          'url' => $url,
          'class' => ['custom-subscribe', 'custom-request'],
        ];
      }
      else {
        // Closed group message.
        $build[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'title' => $this->t('This is a closed group. Only a group administrator can add you.'),
            'class' => ['group', 'closed'],
          ],
          '#value' => $this->t('This is a closed group. Only a group administrator can add you.'),
        ];
        $cache_meta->applyTo($build);
        return $build;
      }
    }

    // Add the link if set.
    if (!empty($link['title'])) {
      $build[] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => $link['url'],
        '#options' => [
          'attributes' => [
            'title' => $link['title'],
            'class' => array_merge(['group'], $link['class']),
          ],
        ],
      ];
    }

    $cache_meta->applyTo($build);
    return $build;
  }

}
