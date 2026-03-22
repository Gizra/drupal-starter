<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies utility account routes use the administration theme.
 */
final class ServerGeneralThemeNegotiatorTest extends ServerGeneralTestBase {

  /**
   * Tests utility user routes render with Claro while public pages do not.
   */
  public function testUserUtilityPagesUseAdminTheme(): void {
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();

    $this->drupalLogin($user);

    $this->drupalGet('/user');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertStringContainsString('/core/themes/claro/', $this->getSession()->getPage()->getContent());
    $this->assertStringNotContainsString('/themes/custom/server_theme/dist/css/style.css', $this->getSession()->getPage()->getContent());

    $this->drupalGet(sprintf('/user/%s', $user->id()));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertStringContainsString('/core/themes/claro/', $this->getSession()->getPage()->getContent());
    $this->assertStringNotContainsString('/themes/custom/server_theme/dist/css/style.css', $this->getSession()->getPage()->getContent());

    $this->drupalGet('/search');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertStringContainsString('"theme":"server_theme"', $this->getSession()->getPage()->getContent());
  }

}
