<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'cta' paragraph type.
 */
class ServerGeneralParagraphCtaTest extends ServerGeneralParagraphTestBase {

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
