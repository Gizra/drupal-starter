<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for font weight options used in theme wrappers.
 */
enum FontWeightEnum: string {
  case Normal = 'normal';
  case Medium = 'medium';
  case Bold = 'bold';
}
