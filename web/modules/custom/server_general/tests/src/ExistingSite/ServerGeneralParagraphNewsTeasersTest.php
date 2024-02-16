<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'views' paragraph type.
 */
class ServerGeneralParagraphNewsTeasersTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'news_teasers';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [];
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
