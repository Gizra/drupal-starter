<?php

namespace Drupal\server_general;

use Drupal\Core\Entity\EntityInterface;

/**
 * Trait ProcessedTextBuilderTrait.
 *
 * Helper method for building a Processed text (e.g. a body field).
 */
trait ProcessedTextBuilderTrait {

  /**
   * Build the body of node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "body".
   *
   * @return array
   *   Render array.
   */
  protected function buildBody(EntityInterface $entity, $field = 'body') {
    $element = $this->buildProcessedText($entity, $field);
    return $this->wrapComponentWithContainer($element, 'content-body-wrapper', 'fluid-container-narrow');
  }

  /**
   * Build a (processed) text of the content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field
   *   Optional; The name of the field. Defaults to "body".
   * @param bool $summary_or_trimmed
   *   Optional; If TRUE then the "summary or trimmed" formatter will be used.
   *   Defaults to FALSE.
   *
   * @return array
   *   Render array.
   */
  protected function buildProcessedText(EntityInterface $entity, $field = 'body', $summary_or_trimmed = FALSE) {
    if ($entity->get($field)->isEmpty()) {
      return [];
    }

    $options = ['label' => 'hidden'];

    if ($summary_or_trimmed) {
      $options['type'] = 'text_summary_or_trimmed';
    }

    return [
      '#theme' => 'server_theme_content__body',
      '#content' => $entity->get($field)->view($options),
    ];
  }

}
