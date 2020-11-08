<?php

namespace Drupal\og_custom\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;

/**
 * Plugin implementation of the 'custom_subscribe_message' formatter.
 *
 * @FieldFormatter(
 *   id = "custom_subscribe_message",
 *   label = @Translation("Custom subscribe message"),
 *   field_types = {
 *     "og_group"
 *   }
 * )
 */
class CustomSubscribeMessageFormatter extends GroupSubscribeFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
   return [
      'subscribe_message' => t('Hi %user, click here if you would like to subscribe to this group called %group'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $defaults = self::defaultSettings();

    $default_value = $defaults['subscribe_message'];
    if (isset($this->settings['subscribe_message'])) {
      $default_value = $this->settings['subscribe_message'];
    }
    $form['sub_message'] = [
      '#title' => $this->t('OG Subscribe Custom Message'),
      '#type' => 'textfield',
      '#description' => $this->t('Use %user token for user name and %group for group name'),
      '#default_value' => $default_value,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if ($elements[0]['#type'] != 'link') {
      return $elements;
    }
    // Don't show the message if:
    // user is anonymous
    $user = $this->entityTypeManager->load(($this->currentUser->id()));
    if (!$user->isAuthenticated()) {
      return $elements;
    }
    // user is already a member
    $group = $items->getEntity();
    if (Og::isMember($group, $user, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING])) {
      return $elements;
    }
    // Finally, show the message for the right user.
    if (($access = $this->ogAccess->userAccess($group, 'subscribe', $user))
      && $access->isAllowed()) {
      $settings = $this->getSettings();
      $tokens = [
        '%user' => $user->getDisplayName(),
        '%group' => $group->label(),
      ];
      $elements[0]['#title'] = self::tokenizeMessage($settings['subscribe_message'], $tokens);
    }
    return $elements;
  }

  /**
   * Replace tokens appropriately before rendering the message.
   *
   * @param string $message
   *   The message
   * @param array $tokens
   *   An array of tokens and their values.
   *
   * @return string
   *   The tokenized message
   */
  public static function tokenizeMessage($message, array $tokens) {
    foreach ($tokens as $token => $value) {
      if (strpos($message, $token) === FALSE) {
        continue;
      }
      $message = str_replace($token, $value, $message);
    }

    return $message;
  }
}
