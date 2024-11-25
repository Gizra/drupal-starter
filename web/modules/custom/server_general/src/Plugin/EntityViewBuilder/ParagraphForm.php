<?php

namespace Drupal\server_general\Plugin\EntityViewBuilder;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\ProcessedTextBuilderTrait;
use Drupal\server_general\TitleAndLabelsTrait;

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

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;
  use TitleAndLabelsTrait;

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

    $elements = [];
    // Build the webform paragraph title.
    if (!$entity->get('field_title')->isEmpty()) {
      $elements[] = $this->buildParagraphTitle($this->getTextFieldValue($entity, 'field_title'));
    }

    // Build the webform paragraph description.
    if (!$entity->get('field_description')->isEmpty()) {
      $elements[] = $this->buildProcessedText($entity, 'field_description');
    }

    // Build the webform.
    /** @var ?\Drupal\webform\WebformInterface $webform */
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_name);
    if (empty($webform) || !$webform->isOpen()) {
      return [];
    }
    $element = $this->entityTypeManager->getViewBuilder('webform')->view($webform);
    $elements[] = $element;

    // Add cache dependencies.
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($webform)
      ->applyTo($build);

    $element = $this->wrapContainerVerticalSpacingBig($elements);
    $build[] = $this->wrapContainerWide($element);

    return $build;
  }

}
