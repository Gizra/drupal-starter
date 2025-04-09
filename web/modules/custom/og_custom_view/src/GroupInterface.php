<?php
namespace Drupal\og_custom_view;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

interface GroupInterface {
  
  public function getMembership(EntityInterface $group, AccountInterface $user);

  public function getSubscribeUrl(EntityInterface $group);

  public function getUnSubscribeUrl(EntityInterface $group);
}
