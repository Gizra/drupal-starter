<?php

declare(strict_types=1);

namespace Drupal\server_general\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\server_general\Service\AiBotRangeUpdater;

/**
 * Cron hook that refreshes the AI-bot IP-range verification cache.
 */
final class AiBotRangeCronHook {

  public function __construct(
    protected readonly AiBotRangeUpdater $aiBotRangeUpdater,
  ) {
  }

  /**
   * Implements hook_cron().
   *
   * Refreshes the cache for the opt-in verification snippet. No-op unless that
   * snippet is enabled, which defines AI_BOT_VERIFICATION_CACHE_FILE.
   */
  #[Hook('cron')]
  public function refreshAiBotRanges(): void {
    if (!defined('AI_BOT_VERIFICATION_CACHE_FILE')) {
      return;
    }
    $this->aiBotRangeUpdater->refreshIfStale();
  }

}
