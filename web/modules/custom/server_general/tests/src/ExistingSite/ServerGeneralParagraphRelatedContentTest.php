<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'related_content' paragraph type.
 */
class ServerGeneralParagraphRelatedContentTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'related_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_title',
      'field_related_content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_link',
    ];
  }

}
