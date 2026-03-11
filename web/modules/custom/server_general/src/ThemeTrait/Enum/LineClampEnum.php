<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for line clamp options used in theme wrappers.
 */
enum LineClampEnum: int {
  case One = 1;
  case Two = 2;
  case Three = 3;
  case Four = 4;
}
