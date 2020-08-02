<?php

namespace Drupal\server_general\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\server_general\ComponentWrapTrait;

/**
 * Class Homepage.
 */
class Homepage extends ControllerBase {

  use ComponentWrapTrait;

  /**
   * {@inheritDoc}
   */
  public function view() {
    $build['main_content'] = $this->buildMainContent();
    return $build;
  }

  /**
   * Build the hero header.
   *
   * @return array
   *   Render array.
   */
  protected function buildMainContent() {
    $element = ['#markup' => $this->t('Add your Homepage element in \Drupal\server_general\Controller\Homepage')];
    return $this->wrapComponentWithContainer($element, 'content-homepage-main-content');
  }

}
