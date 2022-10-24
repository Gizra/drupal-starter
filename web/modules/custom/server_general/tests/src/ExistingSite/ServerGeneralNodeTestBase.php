<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Abstract class to hold shared logic to check various content types.
 */
abstract class ServerGeneralNodeTestBase extends ServerGeneralFieldableEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'node';
  }

}
