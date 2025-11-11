<?php

namespace Drupal\paragraphs_simple_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for showing a Paragraph add form targeted to a host entity.
 */
class ParagraphsSimpleEditController extends ControllerBase {

  /**
   * Title callback.
   */
  public function addParagraphTitle(string $root_parent_type, ContentEntityInterface $root_parent, string $bundle) {
    $paragraph_type = $this->entityTypeManager()->getStorage('paragraphs_type')->load($bundle);
    if ($paragraph_type instanceof ParagraphsTypeInterface) {
      return $this->t('Add new @bundle', [
        '@bundle' => $paragraph_type->label(),
      ]);
    }
    return $this->t('Add new paragraph');
  }

  /**
   * Show paragraph add form.
   */
  public function addParagraph(string $root_parent_type, ContentEntityInterface $root_parent, string $bundle) {
    // Load parent entity storage & the parent entity itself.
    $entity_storage = $this->entityTypeManager()->getStorage($root_parent_type);
    if (!$entity_storage) {
      throw new NotFoundHttpException("Unknown parent entity type: $root_parent_type");
    }

    // Ensure the current user can view the parent (basic protection).
    if (!$root_parent->access('view')) {
      throw new AccessDeniedHttpException("Parent entity not accessible.");
    }

    $paragraph_type = $this->entityTypeManager()->getStorage('paragraphs_type')->load($bundle);
    if (!($paragraph_type instanceof ParagraphsTypeInterface)) {
      throw new NotFoundHttpException("Paragraph bundle not found: $bundle");
    }

    // Create a paragraph entity of the requested bundle (unsaved).
    $paragraph_storage = $this->entityTypeManager()->getStorage('paragraph');

    $paragraph = $paragraph_storage->create(['type' => $bundle]);

    // Access check: can current user create this paragraph bundle?
    if (!$paragraph->access('create')) {
      throw new AccessDeniedHttpException("Access denied while creating paragraph of type $bundle.");
    }

    $paragraph->set('parent_type', $root_parent_type);
    $paragraph->set('parent_id', $root_parent);

    // Render and return the paragraph entity add form.
    // Use the standard entity form builder. We present the default form mode.
    $form = $this->entityFormBuilder()->getForm($paragraph, 'default');

    return $form;
  }

}
