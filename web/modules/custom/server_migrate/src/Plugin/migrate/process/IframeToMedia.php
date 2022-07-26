<?php

declare(strict_types=1);

namespace Drupal\server_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms iframe embedded videos to media embeds.
 *
 * Use this plugin if the files were transliterated during migration.
 *
 * @MigrateProcessPlugin(
 *   id = "iframe_to_media"
 * )
 */
class IframeToMedia extends MediaEmbedProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Regex pattern to extract iframe properties.
   */
  const IFRAME_PATTERN = '/<iframe ([^>]+)><\/iframe>/mui';

  /**
   * Regex pattern to extract the src URL from a properties string.
   */
  const IFRAME_SRC_PATTERN = '/src\="([^"]+)"/ui';

  /**
   * The actual migration plugin instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logging service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity. See MigratePluginManager::createInstance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel "logger.channel.server_migrate" service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL): MigrateProcessInterface {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('server_migrate'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);

    if (!preg_match(self::IFRAME_PATTERN, trim($text))) {
      // No embedded iframe code found.
      return $value;
    }

    // Find all iframes in the text.
    $matches = [];
    preg_match_all(self::IFRAME_PATTERN, trim($text), $matches);
    foreach ($matches[1] as $index => $match) {
      // Get the src attribute value.
      $matches_src = [];
      preg_match(self::IFRAME_SRC_PATTERN, $match, $matches_src);
      if (empty($matches_src[1])) {
        // No src attribute. Malformed iframe code, continue.
        continue;
      }
      // Create or load an existing media with this video URL.
      $media = $this->loadOrCreateVideoMediaByUrl($matches_src[1]);
      // Generate an embed string to replace the iframe code.
      $replace = $this->createEmbedElementString($media->uuid());
      // Replace the iframe code.
      $text = str_replace($matches[0][$index], $replace, $text);
    }
    if ($value_is_array) {
      $value['value'] = $text;
    }
    else {
      $value = $text;
    }
    return $value;
  }

  /**
   * Load or create a new video media entity by given url.
   *
   * @param string $url
   *   The oembed url.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function loadOrCreateVideoMediaByUrl(string $url): MediaInterface {
    // Remove querystrings and anything that follows them.
    $url = preg_replace('/\?.*$/', '', trim($url));
    // Replace 'youtube.com/embed/[id]' urls with 'youtube.com/watch?v=[id]'.
    $embed_pattern = '/youtube[^\.]*\.com\/embed\//ui';
    $url = preg_replace($embed_pattern, 'youtube.com/watch?v=', $url);

    // Try to load an existing video media with that URL.
    /** @var \Drupal\media\MediaInterface[] $media */
    $media = $this->entityTypeManager->getStorage('media')->loadByProperties([
      'bundle' => 'video',
      'field_media_oembed_video' => $url,
    ]);
    if (!empty($media)) {
      // Found existing media. Return the first match.
      return reset($media);
    }
    // Create a new video media.
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => 'video',
      'field_media_oembed_video' => $url,
      'status' => 1,
      // Default to user 1 as creator.
      'uid' => 1,
    ]);
    $media->save();
    return $media;
  }

}
