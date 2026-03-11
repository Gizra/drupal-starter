<?php

declare(strict_types=1);

namespace Drupal\server_general;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\file\FileInterface;
use Drupal\pluggable_entity_view_builder\BuildFieldTrait;
use Drupal\server_general\ThemeTrait\ButtonThemeTrait;

/**
 * Helper methods for getting a button from content.
 */
trait ButtonTrait {

  use ButtonThemeTrait;
  use BuildFieldTrait;

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

    $link = Link::fromTextAndUrl($title, $value['url']);
    return $this->buildButtonPrimary($link);
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
    $link = Link::fromTextAndUrl($title, $value['url']);
    return $this->buildButtonDownload($link);
  }

}
