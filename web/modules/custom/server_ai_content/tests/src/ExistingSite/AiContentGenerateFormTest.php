<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai_content\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the AI content generate form access.
 */
class AiContentGenerateFormTest extends ExistingSiteBase {

  /**
   * Test that anonymous users cannot access the form.
   */
  public function testAnonymousAccessDenied(): void {
    $this->drupalGet('/admin/content/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test that admin users can access the form.
   */
  public function testAdminCanAccessForm(): void {
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('/admin/content/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->fieldExists('prompt');
    $this->assertSession()->buttonExists('Generate');
  }

}
