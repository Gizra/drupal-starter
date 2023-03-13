<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'Quick link item' paragraph type.
 */
class ServerGeneralParagraphQuickLinkItemTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'quick_link';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [];
  }

}
