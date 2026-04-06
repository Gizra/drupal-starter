<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai_content\ExistingSite;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the ContentGenerator service entity creation.
 *
 * @group sequential
 */
class ContentGeneratorTest extends ExistingSiteBase {

  /**
   * The content type machine name used in tests.
   */
  private const TEST_CONTENT_TYPE = 'test_ai_page';

  /**
   * Test paragraph type IDs created during setup.
   *
   * @var string[]
   */
  private const TEST_PARAGRAPH_TYPES = [
    'test_ai_text',
    'test_ai_cta',
    'test_ai_faq_item',
    'test_ai_faq',
    'test_ai_related',
  ];

  /**
   * Test field names created during setup.
   *
   * @var string[]
   */
  private const TEST_FIELDS = [
    'field_test_title',
    'field_test_body',
    'field_test_link',
    'field_test_question',
    'field_test_faq_items',
    'field_test_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Clean up any leftover config from a previous failed run.
    $this->cleanupTestConfig();
    $this->createTestParagraphTypes();
    $this->createTestContentType();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->cleanupTestConfig();
    parent::tearDown();
  }

  /**
   * Remove all test config entities created during setup.
   */
  protected function cleanupTestConfig(): void {
    // Delete field configs on test bundles.
    foreach (self::TEST_FIELDS as $field_name) {
      foreach (self::TEST_PARAGRAPH_TYPES as $bundle) {
        FieldConfig::loadByName('paragraph', $bundle, $field_name)?->delete();
      }
    }
    // field_paragraphs on test node type only.
    FieldConfig::loadByName('node', self::TEST_CONTENT_TYPE, 'field_paragraphs')?->delete();

    // Delete field storage only for test-specific fields (not shared ones
    // like field_paragraphs which may be used by other content types).
    foreach (self::TEST_FIELDS as $field_name) {
      FieldStorageConfig::loadByName('paragraph', $field_name)?->delete();
    }

    // Delete paragraph types.
    foreach (self::TEST_PARAGRAPH_TYPES as $type_id) {
      ParagraphsType::load($type_id)?->delete();
    }

    // Delete node type.
    NodeType::load(self::TEST_CONTENT_TYPE)?->delete();
  }

  /**
   * Test creating a node with simple text paragraph.
   */
  public function testCreateSimpleParagraph(): void {
    $generator = \Drupal::service('server_ai_content.content_generator');

    $data = [
      'title' => 'Test Simple Page',
      'paragraphs' => [
        [
          'type' => 'test_ai_text',
          'fields' => [
            'field_test_title' => 'Test Section',
            'field_test_body' => '<p>Test body content.</p>',
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, self::TEST_CONTENT_TYPE);
    $this->markEntityForCleanup($node);

    $this->assertNotNull($node->id());
    $this->assertEquals('Test Simple Page', $node->getTitle());
    $this->assertFalse($node->isPublished());
    $this->assertEquals(self::TEST_CONTENT_TYPE, $node->bundle());

    $paragraphs = $node->get('field_paragraphs')->referencedEntities();
    $this->assertCount(1, $paragraphs);
    $this->assertEquals('test_ai_text', $paragraphs[0]->bundle());
    $this->assertEquals('Test Section', $paragraphs[0]->get('field_test_title')->value);
    $this->assertEquals('<p>Test body content.</p>', $paragraphs[0]->get('field_test_body')->value);
    $this->assertEquals('full_html', $paragraphs[0]->get('field_test_body')->format);
  }

  /**
   * Test creating a paragraph with a link field.
   */
  public function testCreateParagraphWithLink(): void {
    $generator = \Drupal::service('server_ai_content.content_generator');

    $data = [
      'title' => 'Test Link Page',
      'paragraphs' => [
        [
          'type' => 'test_ai_cta',
          'fields' => [
            'field_test_title' => 'Call to Action',
            'field_test_link' => [
              'uri' => 'https://example.com',
              'title' => 'Click Here',
            ],
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, self::TEST_CONTENT_TYPE);
    $this->markEntityForCleanup($node);
    $paragraphs = $node->get('field_paragraphs')->referencedEntities();

    $this->assertCount(1, $paragraphs);
    $this->assertEquals('test_ai_cta', $paragraphs[0]->bundle());
    $this->assertEquals('https://example.com', $paragraphs[0]->get('field_test_link')->uri);
    $this->assertEquals('Click Here', $paragraphs[0]->get('field_test_link')->title);
  }

  /**
   * Test creating a compound paragraph with sub-paragraphs.
   */
  public function testCreateCompoundParagraphWithSubParagraphs(): void {
    $generator = \Drupal::service('server_ai_content.content_generator');

    $data = [
      'title' => 'Test Compound Page',
      'paragraphs' => [
        [
          'type' => 'test_ai_faq',
          'fields' => [
            'field_test_title' => 'FAQ Section',
            'field_test_faq_items' => [
              [
                'field_test_question' => 'Question 1',
                'field_test_body' => '<p>Answer 1 content.</p>',
              ],
              [
                'field_test_question' => 'Question 2',
                'field_test_body' => '<p>Answer 2 content.</p>',
              ],
              [
                'field_test_question' => 'Question 3',
                'field_test_body' => '<p>Answer 3 content.</p>',
              ],
            ],
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, self::TEST_CONTENT_TYPE);
    $this->markEntityForCleanup($node);
    $paragraphs = $node->get('field_paragraphs')->referencedEntities();

    $this->assertCount(1, $paragraphs);
    $this->assertEquals('test_ai_faq', $paragraphs[0]->bundle());
    $this->assertEquals('FAQ Section', $paragraphs[0]->get('field_test_title')->value);

    $faq_items = $paragraphs[0]->get('field_test_faq_items')->referencedEntities();
    $this->assertCount(3, $faq_items);

    $this->assertEquals('test_ai_faq_item', $faq_items[0]->bundle());
    $this->assertEquals('Question 1', $faq_items[0]->get('field_test_question')->value);
    $this->assertEquals('<p>Answer 1 content.</p>', $faq_items[0]->get('field_test_body')->value);
    $this->assertEquals('full_html', $faq_items[0]->get('field_test_body')->format);

    $this->assertEquals('Question 2', $faq_items[1]->get('field_test_question')->value);
    $this->assertEquals('Question 3', $faq_items[2]->get('field_test_question')->value);
  }

  /**
   * Test creating a node with multiple paragraph types.
   */
  public function testCreateMultipleParagraphTypes(): void {
    $generator = \Drupal::service('server_ai_content.content_generator');

    $data = [
      'title' => 'Test Mixed Page',
      'paragraphs' => [
        [
          'type' => 'test_ai_text',
          'fields' => [
            'field_test_title' => 'Intro',
            'field_test_body' => '<p>Introduction text.</p>',
          ],
        ],
        [
          'type' => 'test_ai_faq',
          'fields' => [
            'field_test_title' => 'Questions',
            'field_test_faq_items' => [
              [
                'field_test_question' => 'How?',
                'field_test_body' => '<p>Like this.</p>',
              ],
            ],
          ],
        ],
        [
          'type' => 'test_ai_cta',
          'fields' => [
            'field_test_title' => 'Act Now',
            'field_test_link' => [
              'uri' => 'route:<nolink>',
              'title' => 'Learn More',
            ],
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, self::TEST_CONTENT_TYPE);
    $this->markEntityForCleanup($node);
    $paragraphs = $node->get('field_paragraphs')->referencedEntities();

    $this->assertCount(3, $paragraphs);
    $this->assertEquals('test_ai_text', $paragraphs[0]->bundle());
    $this->assertEquals('test_ai_faq', $paragraphs[1]->bundle());
    $this->assertEquals('test_ai_cta', $paragraphs[2]->bundle());

    // Verify sub-paragraphs on the FAQ.
    $faq_items = $paragraphs[1]->get('field_test_faq_items')->referencedEntities();
    $this->assertCount(1, $faq_items);
    $this->assertEquals('How?', $faq_items[0]->get('field_test_question')->value);
  }

  /**
   * Test creating a paragraph with entity reference field.
   */
  public function testCreateParagraphWithEntityReference(): void {
    $generator = \Drupal::service('server_ai_content.content_generator');

    // Create a node to reference.
    $referenced_node = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => self::TEST_CONTENT_TYPE,
      'title' => 'Referenced Article',
      'status' => 1,
    ]);
    $referenced_node->save();
    $this->markEntityForCleanup($referenced_node);

    $data = [
      'title' => 'Test Reference Page',
      'paragraphs' => [
        [
          'type' => 'test_ai_related',
          'fields' => [
            'field_test_title' => 'Related Content',
            'field_test_reference' => [
              'target_id' => (int) $referenced_node->id(),
            ],
          ],
        ],
      ],
    ];

    $node = $generator->createFromParsedData($data, self::TEST_CONTENT_TYPE);
    $this->markEntityForCleanup($node);
    $paragraphs = $node->get('field_paragraphs')->referencedEntities();

    $this->assertCount(1, $paragraphs);
    $this->assertEquals('test_ai_related', $paragraphs[0]->bundle());
    $this->assertEquals($referenced_node->id(), $paragraphs[0]->get('field_test_reference')->target_id);
  }

  /**
   * Create test paragraph types for isolated testing.
   */
  protected function createTestParagraphTypes(): void {
    // Simple text paragraph.
    ParagraphsType::create([
      'id' => 'test_ai_text',
      'label' => 'Test AI Text',
    ])->save();
    $this->createField('paragraph', 'test_ai_text', 'field_test_title', 'string');
    $this->createField('paragraph', 'test_ai_text', 'field_test_body', 'text_long');

    // CTA paragraph with link.
    ParagraphsType::create([
      'id' => 'test_ai_cta',
      'label' => 'Test AI CTA',
    ])->save();
    $this->createField('paragraph', 'test_ai_cta', 'field_test_title', 'string');
    $this->createField('paragraph', 'test_ai_cta', 'field_test_link', 'link');

    // FAQ item sub-paragraph.
    ParagraphsType::create([
      'id' => 'test_ai_faq_item',
      'label' => 'Test AI FAQ Item',
    ])->save();
    $this->createField('paragraph', 'test_ai_faq_item', 'field_test_question', 'string');
    $this->createField('paragraph', 'test_ai_faq_item', 'field_test_body', 'text_long');

    // FAQ compound paragraph with sub-paragraphs.
    ParagraphsType::create([
      'id' => 'test_ai_faq',
      'label' => 'Test AI FAQ',
    ])->save();
    $this->createField('paragraph', 'test_ai_faq', 'field_test_title', 'string');
    $this->createEntityReferenceRevisionsField(
      'paragraph',
      'test_ai_faq',
      'field_test_faq_items',
      'paragraph',
      ['test_ai_faq_item'],
    );

    // Related content paragraph with entity reference.
    ParagraphsType::create([
      'id' => 'test_ai_related',
      'label' => 'Test AI Related',
    ])->save();
    $this->createField('paragraph', 'test_ai_related', 'field_test_title', 'string');
    $this->createEntityReferenceField(
      'paragraph',
      'test_ai_related',
      'field_test_reference',
      'node',
      [self::TEST_CONTENT_TYPE],
    );
  }

  /**
   * Create test content type with paragraph reference field.
   */
  protected function createTestContentType(): void {
    NodeType::create([
      'type' => self::TEST_CONTENT_TYPE,
      'name' => 'Test AI Page',
    ])->save();

    $this->createEntityReferenceRevisionsField(
      'node',
      self::TEST_CONTENT_TYPE,
      'field_paragraphs',
      'paragraph',
      [
        'test_ai_text',
        'test_ai_cta',
        'test_ai_faq',
        'test_ai_related',
      ],
    );
  }

  /**
   * Helper to create a simple field on an entity bundle.
   */
  protected function createField(string $entity_type, string $bundle, string $field_name, string $field_type): void {
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => $field_type,
        'cardinality' => 1,
      ])->save();
    }

    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_name,
      ])->save();
    }
  }

  /**
   * Helper to create an entity_reference_revisions field.
   */
  protected function createEntityReferenceRevisionsField(
    string $entity_type,
    string $bundle,
    string $field_name,
    string $target_type,
    array $target_bundles,
  ): void {
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => -1,
        'settings' => [
          'target_type' => $target_type,
        ],
      ])->save();
    }

    $handler_settings = [];
    foreach ($target_bundles as $target_bundle) {
      $handler_settings['target_bundles'][$target_bundle] = $target_bundle;
    }

    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_name,
        'settings' => [
          'handler' => 'default:paragraph',
          'handler_settings' => $handler_settings,
        ],
      ])->save();
    }
  }

  /**
   * Helper to create an entity_reference field.
   */
  protected function createEntityReferenceField(
    string $entity_type,
    string $bundle,
    string $field_name,
    string $target_type,
    array $target_bundles,
  ): void {
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference',
        'cardinality' => 1,
        'settings' => [
          'target_type' => $target_type,
        ],
      ])->save();
    }

    $handler_settings = [];
    foreach ($target_bundles as $target_bundle) {
      $handler_settings['target_bundles'][$target_bundle] = $target_bundle;
    }

    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_name,
        'settings' => [
          'handler' => "default:$target_type",
          'handler_settings' => $handler_settings,
        ],
      ])->save();
    }
  }

}
