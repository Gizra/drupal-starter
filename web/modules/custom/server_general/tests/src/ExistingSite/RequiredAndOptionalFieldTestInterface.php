<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Interface defining required function for required/optional field tests.
 */
interface RequiredAndOptionalFieldTestInterface {

  /**
   * The entity type to assert it exists.
   *
   * @return string
   *   An entity type name.
   */
  public function getEntityType() : string;

  /**
   * The entity bundle to assert it exists.
   *
   * @return string
   *   A bundle name.
   */
  public function getEntityBundle() : string;

  /**
   * The required fields for entity bundle.
   *
   * @return string[]
   *   Array of required field names.
   */
  public function getRequiredFields() : array;

  /**
   * The optional fields for entity bundle.
   *
   * @return string[]
   *   Array of optional field names.
   */
  public function getOptionalFields() : array;

}
