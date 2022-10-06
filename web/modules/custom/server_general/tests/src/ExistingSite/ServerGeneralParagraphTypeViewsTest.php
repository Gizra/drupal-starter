<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'views' paragraph type.
 */
class ServerGeneralParagraphTypeViewsTest extends ServerGeneralParagraphTypeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'views';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_views',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [];
  }

}
