<?php

declare(strict_types=1);

namespace Drupal\server_general\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\server_general\Service\AiBotRangeUpdater;
use Drush\Commands\DrushCommands;

/**
 * Server General Drush commands.
 */
class ServerGeneralCommands extends DrushCommands {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * AI-bot IP-range updater service.
   *
   * @var \Drupal\server_general\Service\AiBotRangeUpdater
   */
  protected AiBotRangeUpdater $aiBotRangeUpdater;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\server_general\Service\AiBotRangeUpdater $ai_bot_range_updater
   *   The AI-bot IP-range updater service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, AiBotRangeUpdater $ai_bot_range_updater) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->aiBotRangeUpdater = $ai_bot_range_updater;
  }

  /**
   * Command description here.
   *
   * @usage server_general:set-homepage
   *   Sets the homepage to the migrated "Homepage" landing page node.
   *
   * @command server_general:set-homepage
   * @aliases set-homepage
   */
  public function setHomepageAfterInstall(): void {
    /** @var \Drupal\node\NodeInterface[] $homepage */
    $homepage = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'title' => 'Homepage',
      'type' => 'landing_page',
      'status' => NodeInterface::PUBLISHED,
    ]);
    /** @var \Drush\Log\DrushLoggerManager|null $logger */
    $logger = $this->logger();
    if (empty($homepage)) {
      $logger->error(dt('Unable to find any published landing_page nodes titled "Homepage".'));
      return;
    }

    $homepage = reset($homepage);
    $front = "/node/{$homepage->id()}";
    $config = $this->configFactory->getEditable('system.site');
    $config->set('page.front', $front);
    $config->save();
    $logger->notice(dt('Homepage set to @front.', [
      '@front' => $front,
    ]));
  }

  /**
   * Fetches the latest AI-bot IP ranges and rewrites the verification cache.
   *
   * Cron refreshes this automatically; use this to seed or force a refresh.
   *
   * @command server_general:refresh-ai-bot-ranges
   * @aliases refresh-ai-bot-ranges
   * @usage drush refresh-ai-bot-ranges
   *   Writes the verification cache from the vendor IP-range lists.
   */
  public function refreshAiBotRanges(): void {
    $count = $this->aiBotRangeUpdater->refresh();
    /** @var \Drush\Log\DrushLoggerManager|null $logger */
    $logger = $this->logger();
    $logger->success(dt('AI-bot range cache written with @count verifiable user-agent tokens.', [
      '@count' => $count,
    ]));
  }

}
