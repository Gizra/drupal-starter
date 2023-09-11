<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'Person teaser' paragraph type.
 */
class ServerGeneralParagraphPersonItemTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'person_teaser';
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
      'field_subtitle',
    ];
  }

}
