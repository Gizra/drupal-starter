<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for text color options used in theme wrappers.
 */
enum TextColorEnum: string {
  case LIGHT_GRAY = 'light-gray';
  case GRAY = 'gray';
  case DARK_GRAY = 'dark-gray';
}
