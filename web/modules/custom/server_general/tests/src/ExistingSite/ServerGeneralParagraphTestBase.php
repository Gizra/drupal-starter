<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Core\File\FileExists;
use Drupal\media\MediaInterface;
use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;

/**
 * Abstract class to hold shared logic to check various paragraph types.
 */
abstract class ServerGeneralParagraphTestBase extends ServerGeneralFieldableEntityTestBase {

  use MediaCreationTrait;
  use ParagraphCreationTrait;

  const IMAGES_PATH = 'modules/custom/server_general/tests/images/';

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'paragraph';
  }

  /**
   * Creates a File entity.
   *
   * @param string $uri
   *   The path of the image to import.
   *
   * @return \Drupal\file\FileInterface
   *   A file entity.
   */
  protected function createFileEntity($uri) {
    $filename = basename($uri);
    $uri = \Drupal::service('file_system')
      ->copy($uri, 'public://' . $filename, FileExists::Replace);
    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create([
        'uri' => $uri,
        'status' => 1,
      ]);
    $file->setPermanent();
    $this->markEntityForCleanup($file);
    $file->save();

    return $file;
  }

  /**
   * Create a media image.
   *
   * @param string $filename
   *   File name of the image.
   *   This file should be present in the directory defined as the IMAGES_PATH
   *   constant in this class.
   *
   * @return \Drupal\media\MediaInterface
   *   The saved Media object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMediaImage($filename = 'test.png'): MediaInterface {
    $file = $this->createFileEntity(self::IMAGES_PATH . $filename);

    $media = $this->createMedia([
      'bundle' => 'image',
      'name' => $filename,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => $filename,
        'title' => $filename,
      ],
    ]);

    return $media;
  }

}
