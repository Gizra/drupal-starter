<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

/**
 * Email testing trait.
 */
trait ServerGeneralMailTestTrait {

  /**
   * Mailpit base URL as provided by DDEV.
   *
   * @return string
   *   Fully qualified URL of Mailpit.
   */
  public function getMailpitBaseUrl(): string {
    $hostnames = getenv('DDEV_HOSTNAME');
    $hostnames = explode(',', $hostnames);
    return 'https://' . reset($hostnames) . ':8026';
  }

  /**
   * Asserts that a string appears in the output of Mailpit.
   *
   * @param string $needle
   *   The string to search for in outgoing emails.
   */
  public function assertOutgoingMailContains(string $needle): void {
    $messages = json_decode(\Drupal::httpClient()->get($this->getMailpitBaseUrl() . '/api/v1/messages')->getBody()->getContents());
    $messages_item_string = '';
    foreach ($messages->messages as $message) {
      $messages_item_string .= $this->decodeSoftReturns(\Drupal::httpClient()->get($this->getMailpitBaseUrl() . '/api/v1/message/' . $message->ID)->getBody()->getContents());
    }
    $this->assertStringContainsString($needle, $messages_item_string);
  }

  /**
   * Asserts that a string does not appear in the output of Mailpit.
   *
   * @param string $needle
   *   The string that should not appear in outgoing emails.
   */
  public function assertOutgoingMailNotContains(string $needle): void {
    $messages = json_decode(\Drupal::httpClient()->get($this->getMailpitBaseUrl() . '/api/v1/messages')->getBody());
    foreach ($messages->messages as $message) {
      $message_item = $this->decodeSoftReturns(\Drupal::httpClient()->get($this->getMailpitBaseUrl() . '/api/v1/message/' . $message->ID)->getBody()->getContents());
      $this->assertStringNotContainsString($needle, $message_item);
    }
  }

  /**
   * Drops the collected outgoing emails in Mailpit.
   */
  public function resetOutgoingMails(): void {
    \Drupal::httpClient()->delete($this->getMailpitBaseUrl() . '/api/v1/messages');
  }

  /**
   * Asserts the number of emails in the Mailpit inbox.
   *
   * @param int $amount
   *   The expected number of emails.
   */
  public function assertOutgoingMailNumber(int $amount): void {
    $messages = json_decode(\Drupal::httpClient()->get($this->getMailpitBaseUrl() . '/api/v1/messages')->getBody()->getContents());
    $this->assertCount($amount, $messages->messages);
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
