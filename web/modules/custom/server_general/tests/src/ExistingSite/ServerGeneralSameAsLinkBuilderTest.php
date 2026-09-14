<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\server_general\JsonLd\SameAsLinkBuilderInterface;

/**
 * Tests the sameAs link builder service.
 *
 * The identifiers below (Wikidata Q42, its VIAF record, a public test ORCID and
 * an OCLC number) are unrelated example authorities used only to exercise the
 * mapping.
 */
class ServerGeneralSameAsLinkBuilderTest extends ServerGeneralTestBase {

  /**
   * The service under test.
   *
   * @var \Drupal\server_general\JsonLd\SameAsLinkBuilderInterface
   */
  protected SameAsLinkBuilderInterface $builder;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->builder = $this->container->get('server_general.same_as_link_builder');
  }

  /**
   * Tests that every supported authority maps to its canonical URL.
   */
  public function testBuildAllAuthorities(): void {
    $result = $this->builder->build([
      'wikidata' => 'Q42',
      'viaf' => '113230702',
      'orcid' => '0000-0002-1825-0097',
      'worldcat' => '44954653',
    ]);

    $this->assertEquals([
      'https://www.wikidata.org/wiki/Q42',
      'https://viaf.org/viaf/113230702',
      'https://orcid.org/0000-0002-1825-0097',
      'https://search.worldcat.org/title/44954653',
    ], $result['sameAs']);
  }

  /**
   * Tests that @id follows the stability convention's priority order.
   *
   * Wikidata outranks VIAF, which outranks ORCID, which outranks WorldCat —
   * regardless of the order the identifiers are passed in.
   */
  public function testStableIdPriority(): void {
    // Wikidata wins when present, even when passed last.
    $result = $this->builder->build([
      'worldcat' => '44954653',
      'orcid' => '0000-0002-1825-0097',
      'viaf' => '113230702',
      'wikidata' => 'Q42',
    ]);
    $this->assertEquals('https://www.wikidata.org/wiki/Q42', $result['@id']);

    // Falls back to VIAF when Wikidata is absent.
    $result = $this->builder->build([
      'worldcat' => '44954653',
      'viaf' => '113230702',
    ]);
    $this->assertEquals('https://viaf.org/viaf/113230702', $result['@id']);

    // Falls back to WorldCat when it is the only authority.
    $result = $this->builder->build(['worldcat' => '44954653']);
    $this->assertEquals('https://search.worldcat.org/title/44954653', $result['@id']);
  }

  /**
   * Tests that empty and malformed identifiers are skipped.
   */
  public function testInvalidIdentifiersAreSkipped(): void {
    // No valid input yields an empty result, not a broken @id or URL.
    $this->assertSame([], $this->builder->build([]));
    $this->assertSame([], $this->builder->build([
      'wikidata' => '',
      'viaf' => '   ',
      // Missing the Q prefix.
      'orcid' => 'not-an-orcid',
    ]));

    // A malformed high-priority id is skipped so a valid lower-priority id
    // still provides the @id.
    $result = $this->builder->build([
      'wikidata' => '42',
      'viaf' => '113230702',
    ]);
    $this->assertEquals('https://viaf.org/viaf/113230702', $result['@id']);
    $this->assertEquals(['https://viaf.org/viaf/113230702'], $result['sameAs']);
  }

  /**
   * Tests multi-value input and de-duplication.
   */
  public function testMultipleValuesAreDeduplicated(): void {
    $result = $this->builder->build([
      'viaf' => ['113230702', '113230702', '75121530'],
    ]);

    $this->assertEquals([
      'https://viaf.org/viaf/113230702',
      'https://viaf.org/viaf/75121530',
    ], $result['sameAs']);
  }

  /**
   * Tests reading identifiers from an entity's fields.
   */
  public function testBuildForEntity(): void {
    // The title field is used as an arbitrary string source here; the field map
    // decouples the service from any specific project's field schema.
    $node = $this->createNode([
      'title' => 'Q42',
      'type' => 'news',
      'moderation_state' => 'published',
    ]);

    $result = $this->builder->buildForEntity($node, [
      'wikidata' => 'title',
      // A field that does not exist on the node is skipped gracefully.
      'viaf' => 'field_does_not_exist',
    ]);

    $this->assertEquals('https://www.wikidata.org/wiki/Q42', $result['@id']);
    $this->assertEquals(['https://www.wikidata.org/wiki/Q42'], $result['sameAs']);
  }

}
