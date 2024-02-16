<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'Accordion item' paragraph type.
 */
class ServerGeneralParagraphAccordionItemTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'accordion_item';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_title',
      'field_body',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [];
  }

}
