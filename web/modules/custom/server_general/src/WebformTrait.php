<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;

/**
 * Trait WebformTrait.
 *
 * Helper method for building a webform.
 */
trait WebformTrait {

  use ElementWrapThemeTrait;
  use ProcessedTextBuilderTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build the webform.
   *
   * @param array $webform
   *   Webform render array.
   * @param string $title
   *   Webform title. Optional.
   * @param array $description
   *   Webform description. Optional.
   *
   * @return array
   *   Render array.
   */
  public function buildWebformWithTitleAndDescription(array $webform, ?string $title = NULL, ?array $description = NULL): array {
    if (empty($webform)) {
      return [];
    }
    $elements = [];

    // Build the webform paragraph title.
    if ($title) {
      $elements[] = $this->buildParagraphTitle($title);
    }

    // Webform descirption.
    if ($description) {
      $elements[] = $description;
    }

    $elements[] = $webform;

    $element = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerWide($element);
  }

  /**
   * Helper to get rendered webform.
   *
   * @param string $webform_name
   *   Webform machine name.
   *
   * @return array
   *   Webform render array.
   */
  public function getWebform(string $webform_name): array {
    $element = [];

    // Build the webform.
    /** @var ?\Drupal\webform\WebformInterface $webform */
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_name);

    // Check if also webform is open for submissions.
    if (empty($webform) || !$webform->isOpen()) {
      return [];
    }

    $element[] = $this->entityTypeManager->getViewBuilder('webform')->view($webform);

    // Add cache dependencies.
    CacheableMetadata::createFromRenderArray($element)
      ->addCacheableDependency($webform)
      ->applyTo($element);

    return $element;
  }

}
