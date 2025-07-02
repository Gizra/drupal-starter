<?php

declare(strict_types=1);

namespace Drupal\server_general\ThemeTrait;

/**
 * Enum for paragraph type options used in theme wrappers.
 */
enum ParagraphTypeEnum: string {
  case Documents = 'documents';
  case Quote = 'quote';
  case RelatedContent = 'related_content';
}