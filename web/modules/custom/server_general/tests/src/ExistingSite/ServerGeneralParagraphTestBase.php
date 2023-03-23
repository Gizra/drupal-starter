<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Abstract class to hold shared logic to check various paragraph types.
 */
abstract class ServerGeneralParagraphTestBase extends ServerGeneralFieldableEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return 'paragraph';
  }

  /**
   * Create a media image.
   *
   * @return \Drupal\media\Entity\Media
   *   The saved Media object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMediaImage(): Media {
    $file = File::create([
      'uri' => 'https://exmaple.com',
    ]);
    $file->save();
    $this->markEntityForCleanup($file);

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Media item',
      'field_media_file' => [
        [
          'target_id' => $file->id(),
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->markEntityForCleanup($media);

    return $media;
  }

}
