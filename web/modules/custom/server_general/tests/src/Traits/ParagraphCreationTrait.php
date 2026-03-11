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
   *   The created Paragraph entity.
   */
  protected function createParagraph(array $settings = []): ParagraphInterface {
    /** @var \Drupal\paragraphs\ParagraphInterface $entity */
    $entity = Paragraph::create($settings);
    $entity->save();
    $this->markEntityForCleanup($entity);

    return $entity;
  }

  /**
   * Creates a Paragraph of a specific type with common defaults.
   *
   * @param string $type
   *   The paragraph type/bundle.
   * @param array $field_values
   *   Additional field values to set on the paragraph.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The created Paragraph entity.
   */
  protected function createParagraphOfType(string $type, array $field_values = []): ParagraphInterface {
    $settings = [
      'type' => $type,
      'status' => 1,
    ] + $field_values;

    return $this->createParagraph($settings);
  }

  /**
   * Creates multiple paragraphs of the same type.
   *
   * @param string $type
   *   The paragraph type/bundle.
   * @param int $count
   *   The number of paragraphs to create.
   * @param array $base_field_values
   *   Base field values for all paragraphs.
   *
   * @return \Drupal\paragraphs\ParagraphInterface[]
   *   Array of created Paragraph entities.
   */
  protected function createMultipleParagraphs(string $type, int $count, array $base_field_values = []): array {
    $paragraphs = [];

    for ($i = 0; $i < $count; $i++) {
      $paragraphs[] = $this->createParagraphOfType($type, $base_field_values);
    }

    return $paragraphs;
  }

}
