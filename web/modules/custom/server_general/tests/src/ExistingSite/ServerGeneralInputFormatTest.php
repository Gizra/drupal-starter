<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test input formats.
 */
class ServerGeneralInputFormatTest extends ExistingSiteBase {

  /**
   * Test Full HTML input format.
   */
  public function testFullHtmlFormat() {
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();

    $this->drupalLogin($user);

    $this->drupalGet('/node/add/news');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Test Page' . time());
    $this->getSession()->getPage()->fillField('edit-field-body-0-value', 'I can have a form: <form class="fun">, but not a script: <script></script>, as that would be way too dangerous. See https://owasp.org/www-community/attacks/xss/. <div class="danger-danger" onmouseover="javascript: whatafunction()">abc</div>');
    $this->getSession()->getPage()->selectFieldOption('edit-field-body-0-format--2', 'full_html');
    $this->click('#edit-submit');
    // <form> tag can be used.
    $this->assertSession()->elementExists('css', '.node--type-news form');
    // The class attribute is preserved.
    $this->assertSession()->elementExists('css', '.node--type-news form.fun');
    // <script> tag is eliminated.
    $this->assertSession()->elementNotExists('css', '.node--type-news script');
    // The onmouseover attribute is completely droppped.
    $this->assertSession()->elementExists('css', '.danger-danger');
    $this->assertStringNotContainsString('onmouseover', $this->getCurrentPage()->getOuterHtml());
    $this->clickLink('Delete');
    $this->click('#edit-submit');
  }

}
