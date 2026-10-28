<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for text color options used in theme wrappers.
 */
enum TextColorEnum: string {
  case DarkGray = 'dark-gray';
  case Gray = 'gray';
  case LightGray = 'light-gray';
}
