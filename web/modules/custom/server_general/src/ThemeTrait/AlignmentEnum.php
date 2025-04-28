<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for alignment options used in theme wrappers.
 */
enum AlignmentEnum: string {

  // The default alignment option.
  case DEFAULT = 'stretch';
  case START = 'start';
  case CENTER = 'center';
  case END = 'end';
}
