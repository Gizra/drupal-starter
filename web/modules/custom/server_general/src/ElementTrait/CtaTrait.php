<?php

declare(strict_types=1);

namespace Drupal\server_general\ElementTrait;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\server_general\ButtonTrait;
use Drupal\server_general\ElementWrapTrait;
use Drupal\server_general\InnerElementLayoutTrait;

/**
 * Helper methods for rendering Call to Action elements.
 */
trait CtaTrait {

  use ButtonTrait;
  use ElementWrapTrait;
  use InnerElementLayoutTrait;

  /**
   * Build a CTA.
   *
   * @param string $title
   *   The title.
   * @param array $body
   *   The body render array.
   * @param \Drupal\Core\Link $link
   *   The button Link object.
   *
   * @return array
   *   Render array.
   */
  protected function buildElementCta(string $title, array $body, Link $link): array {
    $elements = [];

    // Title.
    $element = $title;
    $element = $this->wrapTextResponsiveFontSize($element, '3xl');
    $element = $this->wrapTextCenter($element);
    $elements[] = $this->wrapTextFontWeight($element, 'bold');

    // Text.
    $elements[] = $this->wrapProseText($body);

    // Button.
    $elements[] = $this->buildButton($link->getText(), $link->getUrl(), TRUE, NULL, UrlHelper::isExternal($link->getUrl()->toString()));

    $elements = $this->wrapContainerVerticalSpacingBig($elements, 'center');

    $elements = $this->buildInnerElementLayout($elements, 'light-gray');
    return $this->wrapContainerNarrow($elements);
  }

}
