<?php

declare(strict_types=1);

namespace Drupal\paragraphs_simple_edit\ThemeNegotiator;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Forces server_theme for the paragraph preview route.
 *
 * This ensures preview renders using frontend theme templates and Tailwind CSS,
 * matching what end users actually see.
 */
class ParagraphPreviewThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    return $route_match->getRouteName() === 'paragraphs_simple_edit.paragraph_preview';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match): string {
    return 'server_theme';
  }

}
