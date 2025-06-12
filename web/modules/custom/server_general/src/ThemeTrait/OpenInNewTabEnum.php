<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for defining whether links should open in a new tab.
 */
enum OpenInNewTabEnum: bool {
  case No = FALSE;
  case Yes = TRUE;
}
