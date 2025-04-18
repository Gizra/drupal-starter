<?php

namespace Drupal\home_assignment\Plugin\EntityViewBuilder;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The "Group" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for 'Group' bundle."
 * )
 */
class NodeGroup extends EntityViewBuilderPluginAbstract
{
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    MembershipManagerInterface $og_membership_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $entity_repository,
      $language_manager
    );
    $this->currentUser = $current_user;
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('og.membership_manager')
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
  public function buildFull(array $build, NodeInterface $entity): array
  {
    $user = $this->currentUser;
    $is_member = $this->ogMembershipManager->isMember($entity, $user->id());

    if (!$is_member) {
      $url = Url::fromRoute('og.subscribe', [
        'entity_type_id' => $entity->getEntityTypeId(),
        'group' => $entity->id(),
        'og_membership_type' => 'default',
      ]);

      $build['offer'] = [
        '#markup' => new FormattableMarkup('Hi @name, click here if you would like to <a href=":href">subscribe</a> to this group called @label?', [
          '@name' => 'name',
          '@label' => 'label',
          ':href' => $url->toString(),
        ]),
      ];
    }

    return $build;
  }

}
