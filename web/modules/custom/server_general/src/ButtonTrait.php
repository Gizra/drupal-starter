<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper methods for getting a themed button.
 */
trait ButtonTrait {

  use BuildFieldTrait;

  /**
   * Build a button.
   *
   * @param array|string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The button's title.
   * @param \Drupal\Core\Url $url
   *   The button's URL as Url object.
   * @param bool $is_primary
   *   Whether this is a primary button. Defaults to FALSE.
   * @param string|null $icon
   *   The name of the icon to add as prefix. Allowed values are:
   *   - `download`.
   *   If NULL, no icon would be added. Defaults to NULL.
   * @param bool $open_new_tab
   *   Whether the button should open in a new tab, defaults to FALSE.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButton(array|string|TranslatableMarkup $title, Url $url, bool $is_primary = FALSE, string $icon = NULL, bool $open_new_tab = FALSE): array {
    return [
      '#theme' => 'server_theme_button',
      '#url' => $url,
      '#title'  => $title,
      '#is_primary' => $is_primary,
      '#icon' => $icon,
      '#open_new_tab' => $open_new_tab,
    ];
  }

  /**
   * Get button by link field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The machine name of the field holding the link. Defaults to `field_link`.
   *
   * @return array
   *   Render array.
   */
  protected function buildLinkButton(ContentEntityInterface $entity, string $field_name = 'field_link'): array {
    if ($entity->get($field_name)->isEmpty()) {
      return [];
    }

    $value = $this->getLinkFieldValue($entity, $field_name);
    if (empty($value)) {
      return [];
    }

    // If title is empty, show the URL itself.
    $title = $value['title'] ?? $value['url']->toString();
    return $this->buildButton($title, $value['url']);
  }

  /**
   * Build a download file button from field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The machine name of the field holding the link. Defaults to `field_link`.
   *
   * @return array
   *   Render array.
   */
  protected function buildFileButton(ContentEntityInterface $entity, string $field_name = 'field_file'): array {
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->getReferencedEntityFromField($entity, $field_name);
    if (!$file instanceof FileInterface) {
      return [];
    }

    $value = $this->getLinkFieldValue($entity, $field_name);
    if (empty($value)) {
      return [];
    }

    $title = $value['title'] ?? $this->t('Download');
    return $this->buildButton($title, $value['url']);
  }

}
