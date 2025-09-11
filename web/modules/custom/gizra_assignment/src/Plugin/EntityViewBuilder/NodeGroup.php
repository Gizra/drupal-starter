<?php

namespace Drupal\gizra_assignment\Plugin\EntityViewBuilder;

use Drupal\Core\Url;
use Drupal\gizra_assignment\ThemeTrait\ElementNodeGroupThemeTrait as ThemeTraitElementNodeGroupThemeTrait;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\TagTrait;
use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\LineSeparatorThemeTrait;
use Drupal\server_general\ThemeTrait\LinkThemeTrait;
use Drupal\server_general\ThemeTrait\SearchThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;

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

  use ElementLayoutThemeTrait;
  use ThemeTraitElementNodeGroupThemeTrait;
  use EntityDateTrait;
  use LineSeparatorThemeTrait;
  use LinkThemeTrait;
  use SearchThemeTrait;
  use SocialShareTrait;
  use TagTrait;
  use TitleAndLabelsThemeTrait;

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
    // The node's label.
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    $label = $node_type->label();

    // The hero responsive image.
    $medias = $entity->get('field_featured_image')->referencedEntities();
    $image = $this->buildEntities($medias, 'hero');

    // The subscribe link.
    $subscribe_link = Url::fromRoute('og.subscribe', [
      'entity_type_id' => 'node',
      'group' => $entity->id(),
      'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
    ]);

    // The greetings items.
    $greetings = [];

    // Only if user is still not a member.
    if (
        !Og::isMember($entity, $this->currentUser) &&
        !Og::isMemberPending($entity, $this->currentUser)
      ) {
      $greetings[] = [
        'roles' => ['authenticated'],
        'message' => $this->t(
          'Hi @name, <a href="@subscribe_link">click here</a> if you would like to subscribe to this group called @label.',
          [
            '@name' => $this->currentUser->getAccountName(),
            '@label' => $entity->label(),
            '@subscribe_link' => $subscribe_link->toString(),
          ],
        ),
      ];
    }

    $element = $this->buildElementNodeGroup(
      $entity->label(),
      $label,
      $this->getFieldOrCreatedTimestamp($entity, ''),
      $image,
      $greetings,
      $this->buildProcessedText($entity),
      $this->buildTags($entity),
      $this->buildSocialShare($entity),
    );

    $build[] = $element;

    return $build;
  }

}
