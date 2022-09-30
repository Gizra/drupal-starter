<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Abstract class to hold shared logic to check various content types.
 */
abstract class ServerGeneralContentTypeTestBase extends ServerGeneralEntityTypeTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'node';
  }

}
