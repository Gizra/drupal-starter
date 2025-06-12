<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for button icon options used in theme wrappers.
 */
enum ButtonIconEnum: string {
  case NoIcon = '';
  case Download = 'download';
}
