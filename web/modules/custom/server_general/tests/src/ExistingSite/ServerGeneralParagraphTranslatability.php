<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Tests entity references.
 */
class ServerGeneralParagraphTranslatability extends ServerGeneralTestBase {

  /**
   * Checks Paragraph reference sanity for translations.
   */
  public function testParagraphsTranslation() {
    $em = \Drupal::entityTypeManager();
    $field_configs = $em->getStorage('field_config')->loadMultiple();

    foreach ($field_configs as $id => $field_config) {
      if ($field_config->getType() !== 'entity_reference_revisions') {
        continue;
      }

      $storage_definition = $field_config->getFieldStorageDefinition();
      $referencing = $storage_definition->getSetting('target_type');
      if ($referencing != 'paragraph') {
        continue;
      }

      $this->assertFalse($field_config->isTranslatable(), sprintf("%s must not be translatable as it is a Paragraph field", $id));
    }
  }

}
