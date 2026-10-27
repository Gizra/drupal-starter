<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for underline options used in theme wrappers.
 */
enum UnderlineEnum: string {
  case Always = 'always';
  case Hover = 'hover';
}
