<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use weitzman\DrupalTestTraits\DrupalTrait;

/**
 * Helps in creation of Paragraph entities in tests.
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
    /** @var \Drupal\paragraphs\ParagraphInterface $entity */
    $entity = Paragraph::create($settings);
    $entity->save();
    $this->markEntityForCleanup($entity);

    return $entity;
  }

}
