<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for width options used in theme wrappers.
 */
enum WidthEnum: string {
  case LG = 'lg';
  case XL = 'xl';
  case TWO_XL = '2xl';
  case THREE_XL = '3xl';
}
