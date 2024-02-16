<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ServerGeneralMailTestTrait;

/**
 * A model test case for email-testing using traits from Drupal Test Traits.
 */
class ServerGeneralMailTest extends ServerGeneralTestBase {

  use ServerGeneralMailTestTrait;

  /**
   * Test one-time login links.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testOneTimeLoginLinkEmail() {
    $this->resetOutgoingMails();
    $this->assertOutgoingMailNumber(0);
    $this->drupalGet('user/password');
    $this->getCurrentPage()->fillField('edit-name', 'joe@example.com');
    $this->getCurrentPage()->pressButton('Submit');
    $this->assertOutgoingMailNumber(1);
    $this->assertOutgoingMailContains('Replacement login information for JoeDoe at');
  }

}
