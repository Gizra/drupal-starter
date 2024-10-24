<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\drupal_test_assertions\Assertions\EntityTrait;
use Drupal\Tests\drupal_test_assertions\Assertions\FieldsTrait;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Abstract class to hold shared logic to check various content-types.
 */
abstract class ServerGeneralFieldableEntityTestBase extends ServerGeneralTestBase implements RequiredAndOptionalFieldTestInterface {

  use EntityTrait;
  use FieldsTrait;

  /**
   * Test Required and Not requierd fields for this entity bundle.
   */
  public function testFields() {
    $entity_type = $this->getEntityType();
    $entity_bundle = $this->getEntityBundle();

    $this->assertEntityExists($entity_type, $entity_bundle);

    foreach ($this->getRequiredFields() as $field_name) {
      $this->assertFieldIsRequired($field_name, $entity_type, $entity_bundle);
    }

    foreach ($this->getOptionalFields() as $field_name) {
      $this->assertFieldIsNotRequired($field_name, $entity_type, $entity_bundle);
    }
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
