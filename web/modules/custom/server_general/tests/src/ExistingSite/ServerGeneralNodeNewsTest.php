<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'news' content type.
 */
class ServerGeneralNodeNewsTest extends ServerGeneralNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'news';
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
      'field_featured_image',
      'field_tags',
    ];
  }

}
