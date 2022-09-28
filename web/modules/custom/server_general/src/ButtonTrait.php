<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;

/**
 * Helper methods for getting a themed button.
 */
trait ButtonTrait {

  use BuildFieldTrait;

  /**
   * Get button.
   *
   * @param string $url
   *   The button's URL.
   * @param string $title
   *   The button's title.
   * @param bool $is_primary
   *   Whether this is a primary button. Defaults to FALSE.
   * @param bool $open_new_tab
   *   Whether the button should open in a new tab, defaults to FALSE.
   *
   * @return array
   *   The rendered button array.
   */
  protected function buildButton(string $url, string $title, bool $is_primary = FALSE, bool $open_new_tab = FALSE): array {
    return [
      '#theme' => 'server_theme_button',
      '#url' => $url,
      '#title'  => $title,
      '#is_primary' => $is_primary,
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

    $links = $entity->{$field_name}->getValue();
    $link = reset($links);

    return $this->buildButton(Url::fromUri($link['uri'])->toString(), $link['title']);
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
    $value = $entity->get($field_name)->getValue();
    $title = !empty($value[0]['description']) ? $value[0]['description'] : $this->t('Download');

    return $this->buildButton($file->createFileUrl(), $title);
  }

}
