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
    $this->drupalGet('/node/add/landing_page/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test that users with permission can access the form.
   */
  public function testUserWithPermissionCanAccessForm(): void {
    $user = $this->createUser([
      'generate ai content',
      'create landing_page content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/landing_page/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->fieldExists('prompt');
    $this->assertSession()->buttonExists('Generate');
  }

  /**
   * Test that users without permission cannot access the form.
   */
  public function testUserWithoutPermissionDenied(): void {
    $user = $this->createUser();
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/landing_page/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test that users with AI permission but no node create access are denied.
   */
  public function testUserWithAiPermissionButNoCreateAccessDenied(): void {
    $user = $this->createUser(['generate ai content']);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/landing_page/ai-generate');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test that the Generate with AI link appears on node add forms.
   */
  public function testAiLinkOnNodeAddForm(): void {
    $user = $this->createUser([
      'generate ai content',
      'create landing_page content',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/landing_page');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->linkExists('Generate with AI');
  }

  /**
   * Test that the Generate with AI link is hidden without permission.
   */
  public function testAiLinkHiddenWithoutPermission(): void {
    $user = $this->createUser([
      'create landing_page content',
      'access content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/landing_page');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->linkNotExists('Generate with AI');
  }

}
