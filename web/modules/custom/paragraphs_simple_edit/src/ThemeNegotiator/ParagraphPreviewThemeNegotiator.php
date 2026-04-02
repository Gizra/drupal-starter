<?php

declare(strict_types=1);

namespace Drupal\paragraphs_simple_edit\ThemeNegotiator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Forces the default frontend theme for the paragraph preview route.
 *
 * This ensures preview renders using frontend theme templates and Tailwind CSS,
 * matching what end users actually see.
 */
class ParagraphPreviewThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Constructs a ParagraphPreviewThemeNegotiator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(protected readonly ConfigFactoryInterface $configFactory) {}

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
    return $this->configFactory->get('system.theme')->get('default');
  }

}
