<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
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
  use ElementWrapThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Abstract constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, OgAccessInterface $og_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $entity_repository, $language_manager);
    $this->ogAccess = $og_access;
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
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('og.access')
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
  public function buildFull(array $build, NodeInterface $entity) {
    $this->messenger()->addMessage('Add your Node News elements in \Drupal\server_general\Plugin\EntityViewBuilder\NodeGroup');
    $account = $this->currentUser;
    $elements = [];

    // Title.
    $elements[] = $this->buildPageTitle($entity->getTitle());

    // OG subscribe message
    // First check if user is logged in and allowed to subscribe.
    if ($account->isAuthenticated()) {
      if (($access = $this->ogAccess->userAccess($entity, 'subscribe', $account)) && $access->isAllowed()) {
        $elements[] = [
          '#markup' => $this->t(
            '<em class="subscribe-msg">Hi @name, click here if you would like to subscribe to this group called @label.</em>',
            [
              '@name' => $this->currentUser->getDisplayName(),
              '@label' => $entity->label(),
            ]
          ),
        ];
      }
    }

    // Body.
    $elements[] = $this->buildProcessedText($entity, "body");

    $build[] = $this->wrapContainerWide($this->wrapContainerVerticalSpacingBig($elements));
    return $build;
  }

}
