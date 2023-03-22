<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;

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
   * @return \Drupal\media\Entity\MediaInterface
   *   The saved Media object.
   */
  protected function createMediaImage(): MediaInterface {
    $file = File::create([
      'uri' => 'https://i.pravatar.cc/300',
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
