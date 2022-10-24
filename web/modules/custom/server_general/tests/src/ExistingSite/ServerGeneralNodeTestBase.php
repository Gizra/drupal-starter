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

  /**
   * Extract the reference values for a paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return array
   *   Reference values of the given paragraph containing target_id and
   *   target_revision_id keys.
   */
  protected function getParagraphReferenceValues(ParagraphInterface $paragraph) {
    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}
