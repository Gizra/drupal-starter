<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait\Enum;

/**
 * Enum for font size options used in theme wrappers.
 */
enum FontSizeEnum: string {
  case Xs = 'xs';
  case Sm = 'sm';
  case Base = 'base';
  case LG = 'lg';
  case Xl = 'xl';
  case TwoXl = '2xl';
  case ThreeXl = '3xl';
}
