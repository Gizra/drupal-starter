<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for button type options used in theme wrappers.
 */
enum ButtonTypeEnum: string {
  case Download = 'download';
  case Primary = 'primary';
  case Secondary = 'secondary';
  case Tertiary = 'tertiary';
}
