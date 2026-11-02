<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for alignment options used in theme wrappers.
 */
enum AlignmentEnum: string {

  // The default alignment option.
  case Default = 'stretch';
  case Start = 'start';
  case Center = 'center';
  case End = 'end';
}
