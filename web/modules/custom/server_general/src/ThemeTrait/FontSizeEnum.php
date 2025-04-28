<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for font size options used in theme wrappers.
 */
enum FontSizeEnum: string {
  case XS = 'xs';
  case SM = 'sm';
  case BASE = 'base';
  case LG = 'lg';
  case XL = 'xl';
  case TWO_XL = '2xl';
  case THREE_XL = '3xl';
}
