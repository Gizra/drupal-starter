<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'cta' paragraph type.
 */
class ServerGeneralParagraphTypeCtaTest extends ServerGeneralParagraphTypeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'cta';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_link',
      'field_subtitle',
      'field_title',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [];
  }

}
