<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Helper method for building the Node news element.
 */
trait ElementNodeGroupTrait {

  use TitleAndLabelsTrait;
  use SocialShareTrait;
  use InnerElementTrait;
  use ElementGroupSubscriptionTrait;

    /**
     * Build the Node group element.
     *
     * @param NodeInterface $entity
     *   The Group node entity.
     * @param string $label
     *   The content type label (e.g. `Group`).
     * @param AccountProxy $current_user
     *   Current user.
     * @param \Drupal\Core\Url $url
     *   The Url of the node.
     *
     * @return array
     *   The render array.
     *
     * @throws \IntlException
     * @throws EntityMalformedException
     */
  protected function buildElementNodeGroup(NodeInterface $entity, string $label, AccountProxy $current_user, Url $url): array {
    $elements = [];
    $title = $entity->label();

    // Header.
    $element = $this->buildHeader(
      $title,
      $label
    );
    $elements[] = $this->wrapContainerWide($element);
    // Main content and sidebar.
    $element = $this->buildMainAndSidebar(
      $entity,
      $current_user,
      $this->wrapProseText($this->buildProcessedText($entity)),
      $url
    );
    $elements[] = $this->wrapContainerWide($element);
    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerBottomPadding($elements);
  }

  /**
   * Build the header.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label (e.g. `News`).
   *
   * @return array
   *   Render array.
   *
   * @throws \IntlException
   */
  private function buildHeader(string $title, string $label): array {
    $elements = [];

    $elements[] = $this->buildPageTitle($title);

    // Show the node type as a label.
    $elements[] = $this->buildLabelsFromText([$label]);

    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerMaxWidth($elements, '3xl');
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param NodeInterface $entity
   *   The group entity.
   * @param UserInterface $current_user
   *   Current user.
   * @param array $body
   *   The body render array.
   * @param \Drupal\Core\Url $sub_url
   *   Subscription URL for current group.
   * @param \Drupal\Core\Url $url
   *   The Url of the node.
   *
   * @return array
   *   Render array
   *
   * @throws EntityMalformedException
   */
  private function buildMainAndSidebar(NodeInterface $entity, AccountProxy $current_user, array $body, Url $url): array {
    $title = $entity->label();
    $main_elements = [];
    $user = $current_user;

    if ($this->checkSubscriptionAccess($user, $entity)) {
      $sub_url = $this->getSubscriptionUrl($entity);
      $subscription_message = $this->buildElementGroupSubscription($user->getAccountName(),  $sub_url, $title);
      $main_elements[] = $this->wrapContainerWide($subscription_message, 'light-gray');
    }

    $main_elements[] = $body;

    $sidebar_elements = [];
    $sidebar_elements[] = $this->buildElementSocialShare($title, $url);

    return $this->buildElementLayoutMainAndSidebar(
      $this->wrapContainerVerticalSpacingBig($main_elements),
      $this->buildInnerElementLayout($sidebar_elements),
    );
  }
}
