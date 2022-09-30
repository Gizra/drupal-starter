<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'text' paragraph type.
 */
class ServerGeneralParagraphTypeTextTest extends ServerGeneralParagraphTypeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_body',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
    ];
  }

}
