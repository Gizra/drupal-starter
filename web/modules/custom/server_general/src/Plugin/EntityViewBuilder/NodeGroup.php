<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\intl_date\IntlDate;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\pluggable_entity_view_builder\Annotation\EntityViewBuilder;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\LineSeparatorTrait;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\TitleAndLabelsTrait;
use Drupal\server_general\NodeGroupTrait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * The "OG Group" EVB plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("OG - Group"),
 *   description = "Node view builder for OG Group."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract implements ContainerFactoryPluginInterface {

  use EntityDateTrait;
  use LineSeparatorTrait;
  use SocialShareTrait;
  use TitleAndLabelsTrait;
  use NodeGroupTrait;

  /**
   * OG group settings.
   *
   * @var array $og_group_settings
   */
  protected array $og_group_settings = [
    'override_og_group_field' => FALSE,
  ];

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected OgAccessInterface $ogAccess;

  /**
   * The OG membership service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected MembershipManagerInterface $ogMembershipManager;


  /**
   * NodeGroup constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\og\OgAccessInterface $ogAccess
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, OgAccessInterface $ogAccess, MembershipManagerInterface $ogMembershipManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $entity_repository);

    $this->ogAccess = $ogAccess;
    $this->ogMembershipManager = $ogMembershipManager;
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
      $container->get('og.access'),
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
  public function buildFull(array $build, NodeInterface $entity) {
    $elements = [];

    // Header.
    $element = $this->buildHeader($entity);
    $elements[] = $this->wrapContainerWide($element);

    // Og Group computed field holding (un)subscribe link.
    $this->og_group_settings['override_og_group_field'] = $this->checkMembershipAndSubscribeAccess($entity, $this->currentUser);
    $element = $this->buildOgGroupField($entity, $this->currentUser, $this->og_group_settings['override_og_group_field']);
    $elements[] = $this->wrapContainerWide($element);

    // Main content and sidebar.
    $element = $this->buildMainAndSidebar($entity);
    $elements[] = $this->wrapContainerWide($element);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    $build[] = $this->wrapContainerBottomPadding($elements);

    return $build;
  }

  /**
   * Build the header.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array
   *
   * @throws \IntlException
   */
  protected function buildHeader(NodeInterface $entity): array {
    $elements = [];

    // Build page title.
    $elements[] = $this->buildConditionalPageTitle($entity);

    // Show the node type as a label.
    $node_type = NodeType::load($entity->bundle());
    $elements[] = $this->buildLabelsFromText([$node_type->label()]);

    // Date.
    $timestamp = $this->getFieldOrCreatedTimestamp($entity, 'field_publish_date');
    $element = IntlDate::formatPattern($timestamp, 'long');

    // Make text bigger.
    $elements[] = $this->wrapTextResponsiveFontSize($element, 'lg');

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerNarrow($elements);
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildMainAndSidebar(NodeInterface $entity): array {
    $main_elements = [];
    $sidebar_elements = [];
    $social_share_elements = [];

    // Get the body text, wrap it with `prose` so it's styled.
    // NOTE: Function buildProcessedText has wrong default field value:
    // "field_body" instead of "body" so we pass correct value.
    $main_elements[] = $this->buildProcessedText($entity, 'body');

    // Get the tags, and social share.
    $sidebar_elements[] = $this->buildTags($entity);

    // Show the featured image.
    $medias = $entity->get('field_featured_image')->referencedEntities();
    $sidebar_elements[] = $this->wrapContainerVerticalSpacing($this->buildEntities($medias, 'full'));

    // Add a line separator above the social share buttons.
    $social_share_elements[] = $this->buildLineSeparator();
    $social_share_elements[] = $this->buildSocialShare($entity);

    $sidebar_elements[] = $this->wrapContainerVerticalSpacing($social_share_elements);

    return [
      '#theme' => 'server_theme_main_and_sidebar',
      '#main' => $this->wrapContainerVerticalSpacingBig($main_elements),
      '#sidebar' => $this->wrapContainerVerticalSpacingBig($sidebar_elements),
    ];

  }


  /**
   * Helper - Checks OG group subscribe access and membership status.
   *
   * If user is authenticated and NOT group admin and is not group member
   * already, we enable OG group field content override.
   *
   * @param \Drupal\node\NodeInterface $entity
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   *   Returns TRUE if OG group field should be overridden.
   */
  private function checkMembershipAndSubscribeAccess(NodeInterface $entity, AccountInterface $account) {
    $override = FALSE;

    if ($account->isAuthenticated() && ($entity instanceof EntityOwnerInterface) && ($entity->getOwnerId() != $account->id())) {
      if (!$this->ogMembershipManager->getMembership($entity, $account->id(), OgMembershipInterface::ALL_STATES)) {
          $override = TRUE;
      }
    }

    return $override;
  }

}
