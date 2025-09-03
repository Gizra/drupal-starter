<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\ThemeTrait\ElementNodeGroupThemeTrait;
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
  use ElementNodeGroupThemeTrait;

  /**
   * The renderer.
   *
   * This is not used in this file, but the `SearchThemeTrait` uses it.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The OG access manager.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccessManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->renderer = $container->get('renderer');
    $plugin->account = $container->get('current_user');
    $plugin->ogMembershipManager = $container->get('og.membership_manager');
    $plugin->ogAccessManager = $container->get('og.access');

    return $plugin;
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
  public function buildFull(array $build, NodeInterface $entity) {
    // Group label.
    $group_label = $entity->getTitle();

    $elements = [];
    $element = [];

    // Cache by the OG membership state & authenticated user role.
    $elements['#cache']['contexts'] = [
      'og_membership_state',
      'user.roles:authenticated',
    ];
    $cache_meta = CacheableMetadata::createFromRenderArray($elements);

    $cache_meta->merge(CacheableMetadata::createFromObject($entity));
    $cache_meta->applyTo($elements);

    $user_id = $this->account->id();
    if ($entity->getOwnerId() == $user_id) {
      $element = $this->wrapTextItalic($this->t('You are the group manager.'));
      $build[] = $this->buildElementNodeGroup($entity, $element, $elements);
      return $build;
    }

    // Check for user membership with all states for the group.
    $states = OgMembershipInterface::ALL_STATES;
    $membership = $this->ogMembershipManager->getMembership($entity, $user_id, $states);

    if ($membership) {
      // Memership found. Add cache metadata.
      $cache_meta->merge(CacheableMetadata::createFromObject($membership));
      $cache_meta->applyTo($elements);

      if ($membership->isBlocked()) {
        // User is blocked for the group. They should not be able to apply for
        // subscription.
        $element = $this->wrapTextItalic($this->t('You are blocked from this group.'));
        $build[] = $this->buildElementNodeGroup($entity, $element, $elements);
        return $build;
      }
      // Member is pending or active. We should provide a link to unsubcsribe.
      $parameters = [
        'entity_type_id' => $entity->getEntityTypeId(),
        'group' => $entity->id(),
      ];
      $url = Url::fromRoute('og.unsubscribe', $parameters);

      $content = 'Hi {{ name }}, You are member of this group. Click {{ link }} to unsubscribe.';
      $context = [
        'name' => $this->account->getDisplayName(),
      ];
      $link_text = $this->t('here');
      $element = $this->buildElementGroupText($content, $url, $link_text, $context);
    }
    else {
      if ($this->account->isAuthenticated()) {
        // User us authenticated. Provide a link to subscribe if user has
        // required access to the group.
        $subscribe_no_approval = $this->ogAccessManager->userAccess($entity, 'subscribe without approval', $this->account)->isAllowed();
        $subscribe = $this->ogAccessManager->userAccess($entity, 'subscribe', $this->account)->isAllowed();

        if ($subscribe_no_approval || $subscribe) {
          // User has permission to subscribe to the group.
          $parameters = [
            'entity_type_id' => $entity->getEntityTypeId(),
            'group' => $entity->id(),
            'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
          ];
          $url = Url::fromRoute('og.subscribe', $parameters);
          $content = 'Hi {{ name }}, click {{ link }} if you would like to subscribe to this group called {{ label }}.';
          $context = [
            'name' => $this->account->getDisplayName(),
            'label' => $group_label,
          ];
          $link_text = $this->t('here');
          $element = $this->buildElementGroupText($content, $url, $link_text, $context);
        }
      }
      else {
        // This is anonymous user.
        $cache_meta->setCacheContexts(['user.roles:anonymous']);

        $url = Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
        $content = 'Hi, {{ link }} to subscribe to this group.';
        $link_text = $this->t('log in');
        $element = $this->buildElementGroupText($content, $url, $link_text);
      }
    }

    $cache_meta->applyTo($elements);

    $build[] = $this->buildElementNodeGroup($entity, $element, $elements);

    return $build;
  }

}
