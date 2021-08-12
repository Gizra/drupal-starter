<?php

namespace Drupal\server_general\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\server_general\ElementWrapTrait;

/**
 * A controller to build the "Homepage".
 */
class Homepage extends ControllerBase {

  use ElementWrapTrait;

  /**
   * {@inheritDoc}
   */
  public function view() {
    $build = [];

    $this->messenger()->addMessage('Add your Homepage elements in \Drupal\server_general\Controller\Homepage');

    // Latest content.
    $build[] = $this->buildView();
    return $build;
  }

  /**
   * Build the View.
   *
   * @return array
   *   Render array.
   */
  protected function buildView(): array {
    $element = views_embed_view('frontpage');
    return $this->wrapElementWideContainer($element);
  }

}
