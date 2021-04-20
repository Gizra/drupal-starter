<?php

namespace Drupal\Tests\server_general\Traits;

/**
 * Email testing trait.
 */
trait ServerGeneralMailTestTrait {

  /**
   * Mailhog base URL as provided by DDEV.
   *
   * @return string
   *   Fully qualified URL of Mailhog.
   */
  public function getMailhogBaseUrl() {
    return 'https://' . getenv('DDEV_HOSTNAME') . ':8026';
  }

  /**
   * Asserts that a string appears in the output of Mailhog.
   */
  public function assertOutgoingMailContains(string $needle) {
    $messages = \Drupal::httpClient()->get($this->getMailhogBaseUrl() . '/api/v2/messages')->getBody()->getContents();
    $this->assertContains($needle, $messages);
  }

  /**
   * Asserts that a string does not appear in the output of Mailhog.
   */
  public function assertOutgoingMailNotContains(string $needle) {
    $messages = \Drupal::httpClient()->get($this->getMailhogBaseUrl() . '/api/v2/messages')->getBody()->getContents();
    $this->assertNotContains($needle, $messages);
  }

  /**
   * Drops the collected outgoing emails in Mailhog.
   */
  public function resetOutgoingMails() {
    \Drupal::httpClient()->delete($this->getMailhogBaseUrl() . '/api/v1/messages');
  }

  /**
   * The amount of emails in the inbox of Mailhog.
   *
   * @param int $amount
   *   The amount of emails.
   */
  public function assertOutgoingMailNumber($amount) {
    $messages = json_decode(\Drupal::httpClient()->get($this->getMailhogBaseUrl() . '/api/v2/messages')->getBody());
    $this->assertCount($amount, $messages->items);
  }

}
