<?php

declare(strict_types=1);

namespace Drupal\server_migrate\Plugin\migrate\process;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Masterminds\HTML5;

/**
 * Base class for migrate process plugins which generate media embeds.
 *
 * @property \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
 * @property \Drupal\Core\File\FileSystemInterface $fileSystem
 * @property \Drupal\migrate\Plugin\MigrateProcessInterface $fileCopyPlugin
 * @property \Drupal\Core\Logger\LoggerChannelInterface $logger
 * @property \Symfony\Component\Mime\MimeTypeGuesserInterface $mimeTypeGuesser
 */
abstract class MediaEmbedProcessPluginBase extends ProcessPluginBase {

  /**
   * These are the image extensions that the Image media type accepts.
   */
  const MEDIA_IMAGE_VALID_EXTENSIONS = [
    'gif',
    'jpg',
    'jpeg',
    'png',
  ];

  /**
   * These are the remote video URL bases that the Video media type accepts.
   */
  const MEDIA_VIDEO_VALID_PROVIDERS = [
    'www.youtube.com',
    'player.vimeo.com',
  ];

  /**
   * The ID of the media embed button on the CKEditor.
   *
   * This needs to correspond to the button ID in CKEditor for media embedding.
   * Check for any config in the form of embed.button.[button_id].yml and use
   * the button_id.
   */
  const MEDIA_EMBED_BUTTON_ID = 'media_entity_embed';

  /**
   * View mode to use for the embedded media that's being migrated in.
   */
  const MEDIA_EMBED_VIEW_MODE = 'embed';

  /**
   * Creates a DOM element representing an embed media on the destination.
   *
   * @param \DOMDocument $dom
   *   The \DOMDocument in which the embed \DOMElement is being created.
   * @param string $media_uuid
   *   The UUID of the media which should be represented by the new embed tag.
   *
   * @return \DOMElement
   *   The new embed tag as a writable \DOMElement.
   */
  protected function createEmbedElement(\DOMDocument $dom, $media_uuid): \DOMElement {
    $element = $dom->createElement('drupal-entity');
    $element->setAttribute('data-entity-type', 'media');
    $element->setAttribute('data-entity-uuid', $media_uuid);
    $element->setAttribute('data-embed-button', static::MEDIA_EMBED_BUTTON_ID);
    $element->setAttribute('data-entity-embed-display', 'view_mode:media.' . static::MEDIA_EMBED_VIEW_MODE);
    $element->setAttribute('data-entity-embed-display-settings', '');

    return $element;
  }

  /**
   * Creates an embed element HTML string.
   *
   * @param string $media_uuid
   *   The UUID of the media which should be represented by the new embed tag.
   *
   * @return string
   *   The new embed tag.
   */
  protected function createEmbedElementString(string $media_uuid): string {
    $button_id = static::MEDIA_EMBED_BUTTON_ID;
    $view_mode = static::MEDIA_EMBED_VIEW_MODE;
    return "<drupal-entity data-entity-type=\"media\" data-embed-button=\"{$button_id}\" data-entity-uuid=\"{$media_uuid}\" data-entity-embed-display=\"view_mode:media.{$view_mode}\" data-entity-embed-display-settings=\"\"></drupal-entity>";
  }

  /**
   * Load a Drupal file entity by the URI.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file if found, or NULL if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadFileEntityByUri(string $uri): ?FileInterface {
    /** @var \Drupal\file\FileInterface[] $file */
    $file = $this->entityTypeManager->getStorage('file')->loadByProperties([
      'uri' => $uri,
    ]);
    return empty($file) ? NULL : reset($file);
  }

  /**
   * Loads an existing media entity or create a new one with the given file.
   *
   * @param string $fid
   *   The ID of the file.
   * @param string $label
   *   The media label if a new one should be created.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loadOrCreateImageMediaByFileId(string $fid, string $label): MediaInterface {
    $media_storage = $this->entityTypeManager->getStorage('media');
    /** @var \Drupal\media\MediaInterface[] $media */
    $media = $media_storage->loadByProperties([
      'bundle' => 'image',
      'field_media_image' => $fid,
    ]);
    if (!empty($media)) {
      return reset($media);
    }
    // Create media entity for the image, but we'll only be linking to the.
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->create([
      'bundle' => 'image',
      'name' => $label,
      'field_media_image' => [
        'target_id' => $fid,
      ],
      // Default to user 1 as owner.
      'uid' => 1,
    ]);
    $media->save();
    return $media;
  }

  /**
   * Determine if the source file is an image.
   *
   * @param string $path
   *   The source path.
   *
   * @return bool
   *   TRUE if the source file is an image, FALSE if anything else.
   */
  protected function isImageSource(string $path) {
    if (empty($path)) {
      throw new \Exception('Cannot check image source. Empty path provided.');
    }
    $file_name = $this->getFileNameFromPath($path);
    // Extract the file extension.
    $file_name_parts = explode('.', $file_name);
    $extension = mb_strtolower(end($file_name_parts));
    if (empty($extension)) {
      // No extension, not an image.
      return FALSE;
    }
    return in_array($extension, static::MEDIA_IMAGE_VALID_EXTENSIONS);
  }

  /**
   * Get the file name from the given path.
   *
   * @param string $path
   *   The path to extract the filename from.
   *
   * @return mixed|string
   *   The base file name.
   *
   * @throws \Exception
   */
  protected function getFileNameFromPath(string $path) {
    $file_name = $this->fileSystem->basename($path);
    if (empty($file_name)) {
      throw new \Exception(sprintf('File name cannot be found for path: %s', $path));
    }
    return $file_name;
  }

  /**
   * Replace an img html tag with a media embed replacement.
   *
   * @param \DOMElement $item
   *   The item being replaced, an <img> DOM element.
   * @param \DOMElement $replacement
   *   The replacement item, a <drupal-media> DOM element.
   * @param \Masterminds\HTML5 $html5
   *   The HTML5 parser.
   */
  protected function replaceImgWithMediaEmbed(\DOMElement $item, \DOMElement $replacement, HTML5 $html5): void {
    // Best-effort support for data-align.
    // @see \Drupal\filter\Plugin\Filter\FilterAlign
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Img#attr-align
    if ($item->hasAttribute('align')) {
      $replacement->setAttribute('data-align', $item->getAttribute('align'));
      // Delete the consumed attribute.
      $item->removeAttribute('align');
    }
    if ($item->hasAttribute('style')) {
      $styles = explode(';', $item->getAttribute('style'));
      foreach ($styles as $index => $style) {
        // We have to get the last value of a float style property definition,
        // so we must not have a break here, after the first match.
        if (preg_match('/;float\s*\:\s*(left|right);/', ';' . trim($style) . ';', $matches)) {
          $replacement->setAttribute('data-align', $matches[1]);
          // Remove the float attribute and re-write the style attribute
          // value without the float css property.
          unset($styles[$index]);
          $item->setAttribute('style', implode(';', $styles));
        }
      }
    }

    // Best-effort support for data-caption.
    // @see \Drupal\filter\Plugin\Filter\FilterCaption
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/figcaption
    $target_node = $item;
    if ($item->parentNode->tagName === 'figure') {
      $target_node = $item->parentNode;
      foreach ($item->parentNode->childNodes as $child) {
        if ($child instanceof \DOMElement && $child->tagName === 'figcaption') {
          $caption_html = $html5->saveHTML($child->childNodes);
          $replacement->setAttribute('data-caption', $caption_html);
          break;
        }
      }
    }

    // Retain all other attributes. Currently the media_embed filter
    // explicitly supports the `alt` and `title` attributes, but it may
    // support more attributes in the future. We avoid data loss and allow
    // contrib modules to add more filtering.
    // @see \Drupal\media\Plugin\Filter\MediaEmbed::applyPerEmbedMediaOverrides()
    foreach ($item->attributes as $attribute) {
      if ($attribute->name === 'style' && empty($attribute->value)) {
        // Style attribute is empty after processing. Omit it entirely.
        continue;
      }
      $replacement->setAttribute($attribute->name, $attribute->value);
    }

    $target_node->parentNode->insertBefore($replacement, $target_node);
    $target_node->parentNode->removeChild($target_node);
  }

  /**
   * Copy the remote file to local.
   *
   * @param string $source_url
   *   The remote source URL.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The current migration executable plugin.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param string $destination_property
   *   The destination property.
   * @param string $remote_domain
   *   The remote domain. Include the preceding slash.
   * @param string $destination_uri
   *   Where to save the file.
   *
   * @return \Drupal\file\FileInterface|null
   *   The copied file, or NULL if there was no copy.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doCopyRemoteFile(string $source_url, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property, string $remote_domain, string $destination_uri) {
    $url_parts = $this->parseFileUrl($source_url);

    if (empty($url_parts['path'])) {
      $this->logger->debug(sprintf('Path not found in source URL %s when downloading from remote domain %s.', $source_url, $remote_domain));
      return NULL;
    }

    $source_path = $url_parts['path'];
    if (strpos($source_path, 'sites/default/files/styles/') !== FALSE) {
      // Remove the path to the image style, and download the original.
      $source_path = preg_replace('#/styles/[^\/]+/public/#ui', '/', $source_path);
    }

    $remote_file_url = $remote_domain . $source_path;

    // Check if the file is already in the system. In that case avoid creating
    // duplicates and just use the existing.
    try {
      $file = $this->loadFileEntityByUri($destination_uri);
    }
    catch (\Exception $e) {
      $file = FALSE;
      $this->logger->error('Error occurred loading file by uri: %uri, the error was: @error', [
        '%uri' => $destination_uri,
        '@error' => $e->getMessage(),
      ]);
    }

    // Copy the remote file to local.
    if (!$file instanceof FileInterface) {
      try {
        $plugin_value = NULL;
        // Try local copy if present.
        $local_bases = ['public://'];
        foreach ($local_bases as $local_base) {
          $local_copy = $local_base . $source_path;
          if (file_exists($local_copy)) {
            $plugin_value = [$local_copy, $destination_uri];
            break;
          }
        }
        if (empty($plugin_value)) {
          $plugin_value = [$remote_file_url, $destination_uri];
        }

        $final_destination = $this->fileCopyPlugin->transform($plugin_value, $migrate_executable, $row, $destination_property);
      }
      catch (\Exception $exception) {
        $this->logger->debug(sprintf('Error copying the remote file: %s', $exception->getMessage()));
        return NULL;
      }
    }

    if (empty($final_destination) && !$file instanceof FileInterface) {
      $this->logger->debug(sprintf('Could not copy the remote file located at: %s to %s', $remote_file_url, $destination_uri));
      return NULL;
    }

    if (!$file instanceof FileInterface) {
      // File Copy plugin doesn't create a file entity, so we create one.
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->create();
      $file->setFileUri($final_destination);
      $file->setFilename($this->getFileNameFromPath($source_path));
      try {
        $file->setMimeType($this->mimeTypeGuesser->guessMimeType($final_destination));
      }
      catch (\Exception $exception) {
        $this->logger->debug(sprintf('Could not guess mime type for file at path: %s', $final_destination));
      }
      $file->setPermanent();
      // Default to user 1 as owner.
      $file->setOwnerId(1);
      $file->save();
    }
    return $file;
  }

  /**
   * Parse a file URL.
   *
   * @param string $url
   *   The file URL to parse.
   *
   * @return array
   *   The parsed URL parts or an empty array if the URL is malformed.
   */
  protected function parseFileUrl(string $url): array {
    $url_parts = parse_url($url);
    // File URLs shouldn't have any fragments, but may contain # in the filename
    // which gets parsed as the fragment. We join it back as part of the path.
    // Some URLs could be just anchor links e.g. '#anchor', so we only do the
    // join if path is also set.
    if (isset($url_parts['fragment']) && isset($url_parts['path'])) {
      $url_parts['path'] = implode('#', [
        $url_parts['path'],
        $url_parts['fragment'],
      ]);
    }
    return $url_parts ?: [];
  }

}
