<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for button type options used in theme wrappers.
 */
enum ButtonTypeEnum: string {
  case Primary = 'primary';
  case Secondary = 'secondary';
  case Tertiary = 'tertiary';
}
