<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\drupal_test_assertions\Assertions\EntityTrait;
use Drupal\Tests\drupal_test_assertions\Assertions\FieldsTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Abstract class to hold shared logic to check various content-types.
 */
abstract class ServerGeneralEntityTypeTestBase extends ExistingSiteBase {

  use EntityTrait;
  use FieldsTrait;

  const ENTITY_TYPE = '';
  const ENTITY_BUNDLE = '';
  const REQUIRED_FIELDS = [];
  const OPTIONAL_FIELDS = [];

  /**
   * Test Required and Not requierd fields for this entity bundle.
   */
  public function testFields() {
    $entity_type = static::ENTITY_TYPE;
    $entity_bundle = static::ENTITY_BUNDLE;

    $this->assertEntityExists($entity_type, $entity_bundle);

    foreach (static::REQUIRED_FIELDS as $field_name) {
      $this->assertFieldIsRequired($field_name, $entity_type, $entity_bundle);
    }

    foreach (static::OPTIONAL_FIELDS as $field_name) {
      $this->assertFieldIsNotRequired($field_name, $entity_type, $entity_bundle);
    }
  }

}
