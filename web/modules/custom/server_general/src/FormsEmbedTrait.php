<?php

namespace Drupal\server_general;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Views Embed Trait.
 */
trait FormsEmbedTrait {

  use ElementWrapTrait;
  use ProcessedTextBuilderTrait;
  use TitleAndLabelsTrait;

  /**
   * Builds Webform embed.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   Paragraph entity.
   * @param array $build
   *   Paragraph build array.
   * @param string $webform_field_name
   *   Webform reference field name.
   * @param string|null $field_title
   *   Optional title field name.
   * @param string|null $field_description
   *   Optional description field name.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildWebformEmbed(ParagraphInterface $entity, array $build, string $webform_field_name = 'field_webform', ?string $field_title = NULL, ?string $field_description = NULL): array {
    if (!$entity instanceof ParagraphInterface || empty($webform_field_name)) {
      return $build;
    }

    if (!$entity->hasField($webform_field_name) || empty($entity->{$webform_field_name})) {
      return [];
    }
    $webform_name = $entity->get($webform_field_name)->getValue()[0]['target_id'];
    if (empty($webform_name)) {
      return [];
    }

    $elements = [];
    // Build the webform paragraph title.
    if (!$entity->get($field_title)->isEmpty()) {
      $element = $this->buildParagraphTitle($this->getTextFieldValue($entity, 'field_title'), 'paragraph', FALSE);
      $elements[] = $this->wrapContainerWide($this->wrapContainerHorizontalJustify($element, 'center'));
    }
    // Build the webform paragraph description.
    if (!$entity->get($field_description)->isEmpty()) {
      $element = $this->buildProcessedText($entity, $field_description, FALSE);
      $elements[] = $this->wrapContainerWide($element);
    }

    // Build the webform.
    /** @var ?\Drupal\webform\WebformInterface $webform */
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_name);
    if (empty($webform) || !$webform->isOpen()) {
      return [];
    }
    $element = $this->entityTypeManager->getViewBuilder('webform')->view($webform);
    $elements[] = $this->wrapContainerWide($element);

    // Add cache dependencies.
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($webform)
      ->applyTo($build);

    $build[] = $this->wrapContainerVerticalSpacingBig($elements);

    return $build;
  }

}
