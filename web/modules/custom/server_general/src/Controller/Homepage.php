<?php

namespace Drupal\server_general\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\server_general\ComponentWrapTrait;

/**
 * A controller to build the "Homepage".
 */
class Homepage extends ControllerBase {

  use ComponentWrapTrait;

  /**
   * {@inheritDoc}
   */
  public function view() {
    $build['main_content'] = $this->buildMainContent();
    $build['view'] = $this->buildView();
    return $build;
  }

  /**
   * Build the hero header.
   *
   * @return array
   *   Render array.
   */
  protected function buildMainContent() {
    $element = ['#markup' => $this->t('Add your Homepage elements in \Drupal\server_general\Controller\Homepage')];
    return $this->wrapComponentWithContainer($element, 'content-homepage-main-content');
  }

  /**
   * Build the View.
   *
   * @return array
   *   Render array.
   */
  protected function buildView() {
    $element = views_embed_view('frontpage');
    return $this->wrapComponentWithContainer($element, 'view-homepage-wrapper');
  }

}
