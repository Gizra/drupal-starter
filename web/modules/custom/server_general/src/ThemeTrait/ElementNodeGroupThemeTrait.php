<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\intl_date\IntlDate;
use Drupal\node\NodeInterface;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Drupal\server_general\EntityDateTrait;
use Drupal\server_general\ThemeTrait\Enum\FontSizeEnum;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;

/**
 * Helper method for building the Node group element.
 */
trait ElementNodeGroupThemeTrait {

  use ElementWrapThemeTrait;
  use EntityDateTrait;
  use InnerElementLayoutThemeTrait;
  use LineSeparatorThemeTrait;
  use LinkThemeTrait;
  use ElementLayoutThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build the Node group element.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `Group`).
   * @param int $timestamp
   *   The timestamp.
   * @param \Drupal\node\NodeInterface $entity
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param array $body
   *   The body render array.
   *
   * @return array
   *   The render array.
   *
   * @throws \IntlException
   */
  protected function buildElementNodeGroup(string $title, string $label, int $timestamp, NodeInterface $entity, AccountInterface $current_user, OgAccessInterface $og_access, array $body): array {
    $elements = [];

    // Header.
    $element = $this->buildGroupHeader(
      $title,
      $label,
      $timestamp
    );
    $elements[] = $this->wrapContainerWide($element);

    // Main content.
    $content_elements = [];

    // Build and add subscription message if applicable.
    $subscription_message = $this->buildGroupSubscriptionMessage($entity, $current_user, $og_access);
    if (!empty($subscription_message)) {
      $content_elements[] = $subscription_message;
    }

    // Add body content.
    $content_elements[] = $this->wrapProseText($body);

    $element = $this->wrapContainerVerticalSpacingBig($content_elements);
    $elements[] = $this->wrapContainerWide($element);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerBottomPadding($elements);
  }

  /**
   * Build the group header.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `Group`).
   * @param int $timestamp
   *   The timestamp.
   *
   * @return array
   *   Render array.
   *
   * @throws \IntlException
   */
  private function buildGroupHeader(string $title, string $label, int $timestamp): array {
    $elements = [];

    $elements[] = $this->buildPageTitle($title);

    // Show the node type as a label.
    $elements[] = $this->buildLabelsFromText([$label]);
    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerMaxWidth($elements, WidthEnum::ThreeXl);
  }

  /**
   * Build subscription message for registered users.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   *
   * @return array|null
   *   Render array for subscription message or NULL if not applicable.
   */
  protected function buildGroupSubscriptionMessage(NodeInterface $entity, AccountInterface $current_user, OgAccessInterface $og_access): ?array {
    // Only show message to authenticated users.
    if ($current_user->isAnonymous()) {
      return NULL;
    }

    // Check if user can subscribe to this group.
    $access_result = $og_access->userAccess($entity, 'subscribe', $current_user);

    // If user cannot subscribe, don't show message.
    if ($access_result->isForbidden()) {
      return NULL;
    }

    // Check if user is already a member.
    $membership = Og::getMembership($entity, $current_user);
    if ($membership) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('You are a member of this group.'),
        '#prefix' => '<div class="group-subscription-message">',
        '#suffix' => '</div>',
      ];
    }

    // Build subscription URL.
    $subscription_url = Url::fromRoute('og.subscribe', [
      'entity_type_id' => $entity->getEntityTypeId(),
      'group' => $entity->id(),
      'og_membership_type' => 'default',
    ]);

    $user_name = $current_user->getDisplayName();
    $group_label = $entity->label();

    $markup = [];
    if (Og::isMemberPending($entity, $current_user)) {
      $markup = $this->t('Your membership request is pending review.');
    }
    else {
      $markup = $this->t('Hi @name, <a href="@url">click here</a> if you would like to subscribe to this group called @group_name.', [
        '@name' => $user_name,
        '@url' => $subscription_url->toString(),
        '@group_name' => $group_label,
      ]);
    }

    return [
      '#type' => 'markup',
      '#markup' => $markup,
      '#prefix' => '<div class="group-subscription-message">',
      '#suffix' => '</div>',
    ];
  }

}
