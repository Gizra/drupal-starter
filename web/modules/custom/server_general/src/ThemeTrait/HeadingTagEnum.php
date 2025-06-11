<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for heading tag options used in theme wrappers.
 */
enum HeadingTagEnum: string {

  case H1 = 'h1';
  case H2 = 'h2';
  case H3 = 'h3';
  case H4 = 'h4';
  case H5 = 'h5';
  case H6 = 'h6';
}
