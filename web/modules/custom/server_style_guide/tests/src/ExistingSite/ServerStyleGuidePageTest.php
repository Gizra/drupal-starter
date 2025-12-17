<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Style Guide tests.
 */
class ServerStyleGuidePageTest extends ServerGeneralTestBase {

  /**
   * Anonymous users cannot access Style Guide.
   */
  public function testAnonymousAccess() {
    $this->drupalGet(Url::fromRoute('server_style_guide.style_guide'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test users with certain roles can access the Style Guide.
   *
   * @dataProvider rolesProvider
   */
  public function testRoles(string $role) {
    $user = $this->createUser();
    $user->addRole($role);
    $user->save();

    $this->drupalLogin($user);

    $this->drupalGet(Url::fromRoute('server_style_guide.style_guide'));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

  /**
   * Data provider for testRoles.
   *
   * @return array[]
   *   Array of arrays, containing a role name.
   */
  public function rolesProvider(): array {
    return [
      [
        'content_editor',
      ],
      [
        'translator',
      ],
      [
        'administrator',
      ],
    ];
  }

}
