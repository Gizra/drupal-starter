<?php

declare(strict_types=1);

namespace Drupal\server_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Masterminds\HTML5;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Transforms img tags in text to entity embeds.
 *
 * @code
 * # From this
 * <img src="/sites/default/files/path/to/file.ext" />
 *
 * # To this
 * <drupal-entity
 *   data-embed-button="media"
 *   data-entity-embed-display="view_mode:media.full"
 *   data-entity-type="media"
 *   data-entity-id="1234"></drupal-entity>
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "img_to_media"
 * )
 */
final class ImgToMedia extends MediaEmbedProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The domain where files are copied from.
   *
   * Note: include preceding slash as the source content doesn't include it.
   */
  const FILE_COPY_SOURCE_DOMAIN = 'https://source-site.ddev.site:4443/';

  /**
   * The public file path of Drupal 7.
   */
  const D7_FILE_PUBLIC_PATH = 'sites/default/files';

  /**
   * The destination where the files are copied to.
   *
   * Should be a local stream wrapper URI.
   */
  const FILE_COPY_DESTINATION_DOMAIN = 'public://';

  /**
   * The domain names where the site can be accessed from.
   *
   * In case the img src is pointing at a remote URL, the below domains will be
   * accepted as the source site.
   */
  const DOMAINS = [
    'livedomain.com',
    'www.livedomain.com',
  ];

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * An instance of the download process plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $fileCopyPlugin;

  /**
   * The file mime type guesser service.
   *
   * @var \Symfony\Component\Mime\MimeTypeGuesserInterface
   */
  protected MimeTypeGuesserInterface $mimeTypeGuesser;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel "logger.channel.server_migrate" service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\migrate\Plugin\MigrateProcessInterface $file_copy_plugin
   *   An instance of the file_copy plugin for handling remote file downloads.
   * @param \Symfony\Component\Mime\MimeTypeGuesserInterface $mime_type_guesser
   *   The file mime type guesser service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, FileSystemInterface $file_system, MigrateProcessInterface $file_copy_plugin, MimeTypeGuesserInterface $mime_type_guesser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->fileCopyPlugin = $file_copy_plugin;
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MigrateProcessInterface {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('server_migrate'),
      $container->get('file_system'),
      $container->get('plugin.manager.migrate.process')->createInstance('file_copy', $configuration),
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);
    $lower_text = mb_strtolower($text);
    if (strpos($lower_text, '<img ') === FALSE) {
      // No images.
      return $value;
    }

    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    $dom_text = '<html><body>' . $text . '</body></html>';
    try {
      $dom = $html5->parse($dom_text);
    }
    catch (\TypeError $e) {
      // Unable to parse the text into HTML.
      $this->logger->error('Unable to parse HTML. The error was: %error', [
        '%error' => $e->getMessage(),
      ]);
      return $value;
    }

    // Transform images in text into entity embeds.
    $this->doTransformImages($dom, $migrate_executable, $row, $destination_property, $html5);

    $result = $html5->saveHTML($dom->documentElement->firstChild->childNodes);
    if ($value_is_array) {
      $value['value'] = $result;
    }
    else {
      $value = $result;
    }
    return $value;
  }

  /**
   * Transform img tags to drupal media embeds.
   *
   * @param \DOMDocument $dom
   *   The current DOM being transformed.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The current migration plugin.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param string $destination_property
   *   The destination property.
   * @param \Masterminds\HTML5 $html5
   *   The HTML5 service for parsing text into DOM.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTransformImages(\DOMDocument $dom, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property, HTML5 $html5): void {
    $items = $dom->getElementsByTagName('img');
    $count = $items->count();
    // Each time we remove an image or replace an image with a different
    // element, $items gets updated. So we can't simply iterate over it, as it
    // causes us to skip over files. For example if $items[0] gets removed,
    // $items[1] now becomes $items[0].
    // So the $iterator only gets incremented when we skip processing an image,
    // otherwise it needs to stay the same, and we use this iterator to grab the
    // $item from $items below.
    $iterator = 0;
    for ($i = 0; $i < $count; $i++) {
      /** @var \DOMElement|null $item */
      $item = $items->item($iterator);
      if (empty($item)) {
        $iterator++;
        continue;
      }
      $value = rawurldecode($item->getAttribute('src'));
      $url_parts = $this->parseFileUrl($value);

      // Skip transforming external files. Some links may include a host
      // to prod URL, we'll count them as internal files.
      if (isset($url_parts['host']) && !in_array($url_parts['host'], self::DOMAINS)) {
        // Absolute URL that's not pointing at production.
        $iterator++;
        continue;
      }

      $path = $url_parts['path'];
      if (empty($path) || strpos($path, '/' . self::D7_FILE_PUBLIC_PATH . '/') !== 0) {
        // Not a public file.
        $iterator++;
        continue;
      }

      $escaped_file_path = preg_quote(self::D7_FILE_PUBLIC_PATH, '/');
      // Replace e.g. 'sites/default/files/file.ext' with 'public://file.ext'.
      $file_uri = preg_replace('/^\/' . $escaped_file_path . '\/(.*)$/', self::FILE_COPY_DESTINATION_DOMAIN . '$1', $path);

      $file = $this->doCopyRemoteFile($value, $migrate_executable, $row, $destination_property, self::FILE_COPY_SOURCE_DOMAIN, $file_uri);

      if (empty($file)) {
        $iterator++;
        continue;
      }

      // We try to preserve all existing attributes on the img tag in the new
      // embed, so we delete consumed attributes, like 'src', which should not
      // be carried over to the new element.
      $item->removeAttribute('src');

      $media = $this->loadOrCreateImageMediaByFileId($file->id(), $this->getFileNameFromPath($path));
      $replacement = $this->createEmbedElement($dom, $media->uuid());

      $this->replaceImgWithMediaEmbed($item, $replacement, $html5);
    }
  }

}
