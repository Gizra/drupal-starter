<?php

namespace Drupal\og_custom_view;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgAccessInterface;

class GroupProxy implements GroupInterface, ContainerInjectionInterface {

  use RedirectDestinationTrait;

  public function __construct(
    protected EntityStorageInterface $membership_storage,
    protected OgAccessInterface $og_access
  ){
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('og_membership'),
      $container->get('og.access')
    );
  }

  protected function defaultGreetingsMessages() {
    return [
      'blocked' => '<span> user is blocked </span>',
      'unsubscribe' => '{{ link("unsubscribe", url) }}',
      'subscribe' => '{{ link("subscribe", url) }}',
      'request' => '{{ link("request membership", url) }}',
      'closed' => '<span> group closed </span>'
    ];
  }

  public function userGreeting(EntityInterface $group, AccountInterface $user, array $messages = [])
  {
    $messages += $this->defaultGreetingsMessages(); 
    $element = [
      '#type' => 'inline_template',
      '#context' => [
        'label' => $group->label(),
        'name' => $user->getDisplayName(),
      ]
    ];
    $membership = $this->getMembership($group, $user);
    if ($membership) {
      if ($membership->isBlocked()) {
        $element['#template'] = $messages['blocked'];
      }
      else {
        $element['#template'] = $messages['unsubscribe'];
        $element['#context']['url'] = $this->getUnSubscribeUrl($group);
      }
    }
    else {
      if ($user->isAuthenticated()) {
        $element['#context']['url'] = $this->getSubscribeUrl($group);
      }
      else {
        $element['#context']['url'] = 
          Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
      }
      $access = $this->og_access->userAccess($group, 'subscribe without approval', $user);
      if ($access->isAllowed()) {
        $element['#template'] = $messages['subscribe'];
      }
      else {
        $access = $this->og_access->userAccess($group, 'subscribe', $user);
        if ($access->isAllowed()) {
          $element['#template'] = $messages['request'];
        }
        else {
          $element['#template'] = $messages['closed'];
        }
      }
    }
    return $element;
  }


  public function getMembership(EntityInterface $group, AccountInterface $user) {
    $props = [
      'uid' => $user->id(),
      'entity_type' => $group->getEntityTypeId(),
      'entity_bundle' => $group->bundle(),
      'entity_id' => $group->id(),
    ];
    $memberships = $this->membership_storage->loadByProperties($props);
    return current($memberships);
  }

  public function getSubscribeUrl(EntityInterface $group) {
    $parameters = [
      'entity_type_id' => $group->getEntityTypeId(),
      'group' => $group->id(),
      'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
    ];

    return Url::fromRoute('og.subscribe', $parameters);
  }

  public function getUnSubscribeUrl(EntityInterface $group) {
    $parameters = [
      'entity_type_id' => $group->getEntityTypeId(),
      'group' => $group->id(),
      'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
    ];

    return Url::fromRoute('og.subscribe', $parameters);
  }
}
