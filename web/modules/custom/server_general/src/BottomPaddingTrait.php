<?php

namespace Drupal\server_general;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Helper trait for adding a conditional bottom padding.
 */
trait BottomPaddingTrait {

  use ElementWrapTrait;

  /**
   * Conditionally wrap an element with bottom padding.
   *
   * @param array $element
   *   Render array.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_item_list
   *   The field object where the referenced items are stored.
   *
   * @return array
   *   Render array.
   */
  public function wrapConditionalContainerBottomPadding(array $element, EntityReferenceFieldItemListInterface $field_item_list) {
    // The paragraph types that don't require a bottom padding, if they are
    // the last paragraph on the page.
    $paragraph_types_with_no_bottom_padding = [
      'documents',
    ];

    if ($field_item_list->isEmpty()) {
      return $element;
    }

    $paragraphs = $field_item_list->referencedEntities();
    $count = count($paragraphs);
    $paragraph = $paragraphs[$count - 1];

    if (!($paragraph instanceof ParagraphInterface)) {
      return $element;
    }

    return in_array($paragraph->bundle(), $paragraph_types_with_no_bottom_padding) ? $element : $this->wrapContainerBottomPadding($element);

  }

}
