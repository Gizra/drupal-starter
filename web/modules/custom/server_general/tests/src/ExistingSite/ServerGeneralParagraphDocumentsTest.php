<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Tests\server_general\Traits\ParagraphCreationTrait;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'Documents' paragraph type.
 */
class ServerGeneralParagraphDocumentsTest extends ServerGeneralParagraphTestBase {

  use ParagraphCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'documents';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [
      'field_documents',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_title',
      'field_body',
    ];
  }

  /**
   * Test render of the paragraph.
   */
  public function testRender() {
    $title = 'This is the title';
    $body = 'This is the description';

    $paragraph = $this->createParagraph([
      'type' => $this->getEntityBundle(),
      'field_title' => $title,
      'field_body' => $body,
    ]);

    // Create several Media documents.
    $file = File::create([
      'uri' => 'https://example.com',
    ]);
    $file->save();
    $this->markEntityForCleanup($file);

    $medias = [];
    $media_count = 5;
    foreach (range(1, $media_count) as $key) {
      $media = Media::create([
        'bundle' => 'document',
        'name' => 'Media item ' . $key,
        'field_media_file' => [
          [
            'target_id' => $file->id(),
            'alt' => 'default alt',
            'title' => 'default title',
          ],
        ],
      ]);
      $this->markEntityForCleanup($media);
      $medias[] = $media;
    }

    $paragraph->set('field_documents', $medias);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);

    $user = $this->createUser();
    $node = $this->createNode([
      'title' => 'Landing Page',
      'type' => 'landing_page',
      'uid' => $user->id(),
      'field_paragraphs' => [
        $this->getParagraphReferenceValues($paragraph),
      ],
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->elementTextContains('css', '.paragraph--type--documents', $title);
    $this->assertSession()->elementTextContains('css', '.paragraph--type--documents', $body);

    // Assert all media items are there.
    $this->assertSession()->elementsCount('css', '.paragraph--type--documents .media--type-document', $media_count);

    // Assert 3 media items are hidden.
    $this->assertSession()->elementsCount('css', '.paragraph--type--documents .hidden .media--type-document', 3);

    // Assert view more button.
    $this->assertSession()->elementTextContains('css', '.paragraph--type--documents .button-wrapper', 'View more');

  }

}
