<?php

namespace Drupal\Tests\server_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests entity references.
 */
class ServerGeneralEntityReferecesSelect2Test extends ExistingSiteBase {

  /**
   * Checks all entity references referencing nodes use the select2 widget.
   */
  public function testSelect2Widget() {
    $em = \Drupal::entityTypeManager();
    $field_configs = $em->getStorage('field_config')->loadMultiple();
    $form_display_storage = $em->getStorage('entity_form_display');

    foreach ($field_configs as $id => $field_config) {
      if ($field_config->getType() !== 'entity_reference') {
        continue;
      }

      $storage_definition = $field_config->getFieldStorageDefinition();
      $referencing = $storage_definition->getSetting('target_type');
      if ($referencing != 'node') {
        continue;
      }

      [$entity_type, $bundle, $field_name] = explode('.', $id);

      $form_config = $form_display_storage->load(sprintf("%s.%s.%s", $entity_type, $bundle, 'default'));
      $settings = $form_config->getComponent($field_name);

      $this->assertEquals(
        'select2_entity_reference',
        $settings['type'],
        sprintf("%s (%s) should use select2 but it uses %s", $field_name, $bundle, $settings['type'])
      );
    }
  }

}
