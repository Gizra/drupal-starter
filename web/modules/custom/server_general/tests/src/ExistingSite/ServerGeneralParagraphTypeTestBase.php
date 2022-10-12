<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Abstract class to hold shared logic to check various paragraph types.
 */
abstract class ServerGeneralParagraphTypeTestBase extends ServerGeneralFieldableEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'paragraph';
  }

}
