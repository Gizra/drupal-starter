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

    // Main content.
    $build[] = $this->buildMainContent();

    // Latest content.
    $build[] = $this->buildView();
    return $build;
  }

  /**
   * Build the main content.
   *
   * @return array
   *   Render array.
   */
  protected function buildMainContent(): array {
    $element = [
      '#markup' => $this->t('Add your Homepage elements in \Drupal\server_general\Controller\Homepage'),
    ];

    return $this->wrapElementWideContainer($element);
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
