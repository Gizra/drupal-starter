<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Tests paragraph reference fields.
 */
class ServerGeneralParagraphReferenceSanityTest extends ServerGeneralTestBase {

  /**
   * Checks that all paragraph reference fields are entity reference revisions.
   */
  public function testParagraphReferenceFields() {
    $field_configs = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple();

    foreach ($field_configs as $id => $field_config) {
      $target_type = $field_config->getFieldStorageDefinition()->getSetting('target_type');

      if ($target_type !== 'paragraph') {
        continue;
      }

      $field_type = $field_config->getType();
      $this->assertEquals('entity_reference_revisions', $field_type, sprintf("Field %s referencing paragraphs must be of type 'entity_reference_revisions', not '%s'", $id, $field_type));
    }
  }

}
