<?php

declare(strict_types=1);

namespace Drupal\server_ai;

/**
 * The result of a sensitivity classification of one user question.
 */
final class SensitivityVerdict {

  public function __construct(
    public readonly bool $flagged,
    public readonly string $reason,
  ) {}

}
