<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for background color options used in theme wrappers.
 */
enum BackgroundColorEnum: string {
  case LIGHT_GRAY = 'light-gray';
  case TRANSPARENT = 'transparent';
}
