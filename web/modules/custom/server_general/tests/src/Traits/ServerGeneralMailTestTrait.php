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
    $messages = $this->decodeSoftReturns(\Drupal::httpClient()->get($this->getMailhogBaseUrl() . '/api/v2/messages')->getBody()->getContents());
    $this->assertStringContainsString($needle, $messages);
  }

  /**
   * Asserts that a string does not appear in the output of Mailhog.
   */
  public function assertOutgoingMailNotContains(string $needle) {
    $messages = $this->decodeSoftReturns(\Drupal::httpClient()->get($this->getMailhogBaseUrl() . '/api/v2/messages')->getBody()->getContents());
    $this->assertStringNotContainsString($needle, $messages);
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

  /**
   * Replace =\r\n characters signifying a soft-return.
   *
   * The email messages contain soft-return characters which get inserted at
   * every Xth character by PHPMailer. These are still contained in the emails
   * that we get from the API. This can mean that some strings are cut up into
   * multiple lines, and may fail the test due to having extra characters within
   * the word.
   *
   * @param string $message
   *   The message.
   *
   * @return string
   *   The decoded message.
   *
   * @see https://github.com/PHPMailer/PHPMailer/issues/563
   * @see https://en.wikipedia.org/wiki/Quoted-printable
   */
  protected function decodeSoftReturns(string $message): string {
    return str_replace('=\r\n', '', $message);
  }

}
