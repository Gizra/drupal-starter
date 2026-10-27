<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'text' paragraph type.
 */
class ServerGeneralParagraphTextTest extends ServerGeneralParagraphTestBase {

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
