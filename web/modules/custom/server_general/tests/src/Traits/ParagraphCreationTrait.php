<?php

namespace Drupal\Tests\server_general\Traits;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use weitzman\DrupalTestTraits\DrupalTrait;

/**
 * Wraps the node creation trait to track entities for deletion.
 */
trait ParagraphCreationTrait {

  use DrupalTrait;

  /**
   * Creates a Paragraph and marks it for automatic cleanup.
   *
   * @param array $settings
   *   The settings to pass to Paragraph creation.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The Paragraph.
   */
  protected function createParagraph(array $settings = []): ParagraphInterface {
    $entity = Paragraph::create($settings);
    $entity->save();
    $this->markEntityForCleanup($entity);
    return $entity;
  }

}
