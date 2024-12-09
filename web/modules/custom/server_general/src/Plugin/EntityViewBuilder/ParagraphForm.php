<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\WebformTrait;

/**
 * The "Form" paragraph plugin.
 *
 * @EntityViewBuilder(
 *   id = "paragraph.form",
 *   label = @Translation("Paragraph - Form"),
 *   description = "Paragraph view builder for 'Forms from Webform' bundle."
 * )
 */
class ParagraphForm extends EntityViewBuilderPluginAbstract {

  use ProcessedTextBuilderTrait;
  use WebformTrait;

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, ParagraphInterface $entity): array {

    if (!$entity->hasField('field_webform') && $entity->get('field_webform')->isEmpty()) {
      return [];
    }

    $webform_name = $entity->get('field_webform')->getValue()[0]['target_id'];
    if (empty($webform_name)) {
      return [];
    }

    $webform = $this->getWebform($webform_name);

    if (empty($webform)) {
      return [];
    }

    $title = $this->getTextFieldValue($entity, 'field_title');
    $description = $this->buildProcessedText($entity, 'field_description');

    $build[] = $this->buildWebformWithTitleAndDescription($webform, $title, $description);

    return $build;
  }

}
