<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\Traits;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\server_general\TestConfiguration;

/**
 * Provides common file and media creation functionality for tests.
 *
 * This trait contains methods for creating files and media entities
 * commonly used across multiple test classes.
 */
trait FileMediaCreationTrait {

  /**
   * Creates a test file entity.
   *
   * @param string $filename
   *   The filename to use. Defaults to test.png.
   * @param string $uri_scheme
   *   The URI scheme to use. Defaults to 'public'.
   *
   * @return \Drupal\file\FileInterface
   *   The created file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTestFile(string $filename = TestConfiguration::DEFAULT_TEST_IMAGE, string $uri_scheme = 'public'): FileInterface {
    $source_path = TestConfiguration::getTestImagePath($filename);
    $destination = $uri_scheme . '://' . $filename;
    
    // Copy the test file to the destination.
    file_unmanaged_copy($source_path, $destination, FILE_EXISTS_REPLACE);
    
    $file = File::create([
      'uri' => $destination,
      'filename' => $filename,
      'status' => 1,
    ]);
    $file->save();
    
    return $file;
  }

  /**
   * Creates a test media entity.
   *
   * @param string $media_type
   *   The media type bundle. Defaults to 'image'.
   * @param string $filename
   *   The filename to use for the file. Defaults to test.png.
   * @param array $additional_values
   *   Additional values to set on the media entity.
   *
   * @return \Drupal\media\MediaInterface
   *   The created media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTestMedia(string $media_type = 'image', string $filename = TestConfiguration::DEFAULT_TEST_IMAGE, array $additional_values = []): MediaInterface {
    $file = $this->createTestFile($filename);
    
    $media_values = [
      'bundle' => $media_type,
      'name' => 'Test ' . $media_type,
      'status' => 1,
    ] + $additional_values;

    // Set the appropriate field based on media type.
    switch ($media_type) {
      case 'image':
        $media_values['field_media_image'] = $file->id();
        break;
        
      case 'document':
        $media_values['field_media_document'] = $file->id();
        break;
        
      case 'video':
        $media_values['field_media_video_file'] = $file->id();
        break;
        
      default:
        // For custom media types, assume the field follows the pattern.
        $media_values['field_media_' . $media_type] = $file->id();
        break;
    }
    
    $media = Media::create($media_values);
    $media->save();
    
    return $media;
  }

  /**
   * Creates multiple test media entities of the same type.
   *
   * @param int $count
   *   The number of media entities to create.
   * @param string $media_type
   *   The media type bundle.
   * @param array $base_values
   *   Base values to use for all media entities.
   *
   * @return \Drupal\media\MediaInterface[]
   *   Array of created media entities.
   */
  protected function createMultipleTestMedia(int $count, string $media_type = 'image', array $base_values = []): array {
    $media_entities = [];
    
    for ($i = 0; $i < $count; $i++) {
      $values = $base_values;
      if (!isset($values['name'])) {
        $values['name'] = "Test {$media_type} {$i}";
      }
      
      $media_entities[] = $this->createTestMedia($media_type, TestConfiguration::DEFAULT_TEST_IMAGE, $values);
    }
    
    return $media_entities;
  }

}