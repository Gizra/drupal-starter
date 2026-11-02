<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for width options used in theme wrappers.
 */
enum WidthEnum: string {
  case Lg = 'lg';
  case Xl = 'xl';
  case TwoXl = '2xl';
  case ThreeXl = '3xl';
}
