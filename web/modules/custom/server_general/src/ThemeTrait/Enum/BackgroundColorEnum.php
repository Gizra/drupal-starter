<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for background color options used in theme wrappers.
 */
enum BackgroundColorEnum: string {
  case LightGray = 'light-gray';
  case Transparent = 'transparent';
}
