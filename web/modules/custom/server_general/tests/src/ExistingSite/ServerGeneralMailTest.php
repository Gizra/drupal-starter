<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ServerGeneralMailTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * A model test case for email-testing using traits from Drupal Test Traits.
 */
class ServerGeneralMailTest extends ExistingSiteBase {

  use ServerGeneralMailTestTrait;

  /**
   * An example test method; note that Drupal API's and Mink are available.
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
    $this->assertOutgoingMailContains('Replacement login information for JoeDoe at Drupal Starter');
  }

}
