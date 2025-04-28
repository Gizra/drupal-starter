<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for font weight options used in theme wrappers.
 */
enum FontWeightEnum: string {
  case NORMAL = 'normal';
  case MEDIUM = 'medium';
  case BOLD = 'bold';
}
