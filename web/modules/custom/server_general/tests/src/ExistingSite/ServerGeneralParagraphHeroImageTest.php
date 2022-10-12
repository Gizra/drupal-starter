<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'hero_image' paragraph type.
 */
class ServerGeneralParagraphHeroImageTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'hero_image';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_title',
      'field_image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_link',
      'field_subtitle',
    ];
  }

}
