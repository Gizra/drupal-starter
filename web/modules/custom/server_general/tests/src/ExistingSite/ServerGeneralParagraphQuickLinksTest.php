<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'Quick link' paragraph type.
 */
class ServerGeneralParagraphQuickLinksTest extends ServerGeneralParagraphTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'quick_links';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_quick_link_items',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [];
  }

}
