<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Tests paragraph reference fields.
 */
class ServerGeneralParagraphReferenceSanity extends ServerGeneralTestBase {

  /**
   * Checks that all paragraph reference fields are entity reference revisions.
   */
  public function testParagraphReferenceFields() {
    $em = \Drupal::entityTypeManager();
    $field_configs = $em->getStorage('field_config')->loadMultiple();

    foreach ($field_configs as $id => $field_config) {
      $storage_definition = $field_config->getFieldStorageDefinition();
      $target_type = $storage_definition->getSetting('target_type');

      if ($target_type !== 'paragraph') {
        continue;
      }

      $field_type = $field_config->getType();
      $this->assertEquals('entity_reference_revisions', $field_type, sprintf("Field %s referencing paragraphs must be of type 'entity_reference_revisions', not '%s'", $id, $field_type));
    }
  }

}
