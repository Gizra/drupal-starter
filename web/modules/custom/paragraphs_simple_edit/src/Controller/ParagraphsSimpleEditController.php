<?php

namespace Drupal\paragraphs_simple_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for showing a Paragraph add form targeted to a host entity.
 */
class ParagraphsSimpleEditController extends ControllerBase {

  /**
   * Show paragraph add form.
   */
  public function add($root_parent_type, $root_parent, $bundle, Request $request) {

    // Load host entity storage and the host entity itself.
    // $entity_storage = $this->entityTypeManager->getStorage($host_entity_type);
    // if (!$entity_storage) {
    //   throw new NotFoundHttpException("Unknown host entity type: $host_entity_type");
    // }

    // $host = $entity_storage->load($host_entity);
    // if (!$host) {
    //   throw new NotFoundHttpException("Host entity not found: $host_entity_type $host_entity");
    // }

    // // Ensure the current user can view the host (basic protection).
    // if (!$host->access('view')) {
    //   throw new NotFoundHttpException("Host entity not accessible.");
    // }

    // Create a paragraph entity of the requested bundle (unsaved).
    //$paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    // if (!$paragraph_storage) {
    //   // Paragraph module not enabled or entity type missing.
    //   throw new NotFoundHttpException("Paragraph entity type is not available on this site.");
    // }

    //$paragraph = $paragraph_storage->create(['type' => $paragraph_type]);

    // Access check: can current user create this paragraph bundle?
    // if (!$paragraph->access('create')) {
    //   // Use access denied rather than 404 if you prefer.
    //   return $this->accessDenied();
    // }

    // OPTIONAL / HELPFUL: If you want the paragraph form to know which host it
    // will be attached to (so you can attach in the save path), you can add
    // these as form state values or as query parameters. Here we inject them
    // as #attached form build info (a small, non-invasive approach):
    //
    // The form can read these values in a hook_form_alter or in a custom form
    // submit handler.
    // $paragraph->parent_type = $host_entity_type;
    // $paragraph->parent_id = $host_entity;

    // Render and return the paragraph entity add form.
    // Use the standard entity form builder. We present the default form mode.
    //$form = $this->entityFormBuilder->getForm($paragraph, 'add');

    // Attach the host info as a hidden form element so submit handlers can
    // pick it up easily. We must alter the built form structure here.
    // Only add if form is an array and not cached markup.
    // if (is_array($form)) {
    //   $form['#cache']['contexts'][] = 'user'; // conservative cache context
    //   // Add hidden values for downstream submit handlers.
    //   $form['mymodule_host_info'] = [
    //     '#type' => 'value',
    //     '#value' => [
    //       'host_entity_type' => $host_entity_type,
    //       'host_entity_id' => $host_entity,
    //     ],
    //   ];
    // }

    $form['test'] = [
      '#markup' => 'test',
    ];

    return $form;
  }

}
