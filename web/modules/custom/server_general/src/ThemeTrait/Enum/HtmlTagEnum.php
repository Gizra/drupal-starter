<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for HTML tag options used in theme wrappers.
 */
enum HtmlTagEnum: string {
  case H1 = 'h1';
  case H2 = 'h2';
  case H3 = 'h3';
  case H4 = 'h4';
  case H5 = 'h5';
}
