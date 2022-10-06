<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\drupal_test_assertions\Assertions\EntityTrait;
use Drupal\Tests\drupal_test_assertions\Assertions\FieldsTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Abstract class to hold shared logic to check various content-types.
 */
abstract class ServerGeneralEntityTypeTestBase extends ExistingSiteBase implements RequiredAndOptionalFieldTestInterface {

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

}
