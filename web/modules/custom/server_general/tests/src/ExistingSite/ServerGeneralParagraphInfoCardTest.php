<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'Info card' paragraph type.
 */
class ServerGeneralParagraphInfoCardTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'info_card';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_info_card_header',
      'field_title',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_subtitle',
    ];
  }

}
