<?php

declare(strict_types=1);

namespace Drupal\server_general\JsonLd;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Builds schema.org sameAs links and a stable @id from authority identifiers.
 *
 * External authority records (Wikidata, VIAF, ORCID, WorldCat) uniquely and
 * durably identify a real-world entity — a person, a work, an organization.
 * Emitting them as schema.org `sameAs` values, together with a stable `@id`,
 * lets search engines and knowledge graphs merge every page that references the
 * same entity into a single node in their graph.
 *
 * ## The @id-stability convention
 *
 * The `@id` must be a globally stable, canonical URI for the real-world entity,
 * NOT a site-local URL. A node's canonical path changes across environments
 * (local, staging, production), when the alias is edited, or when content is
 * migrated — so it can never serve as a durable graph identifier. An authority
 * URI never changes: `https://www.wikidata.org/wiki/Q42` denotes the same
 * entity forever, on every site that references it.
 *
 * This builder therefore derives `@id` from the highest-priority authority
 * identifier available, in this order:
 *
 *   1. Wikidata  — broadest cross-domain coverage and the richest hub of
 *                  onward links, so it makes the most useful `@id`.
 *   2. VIAF      — authoritative for people and organizations.
 *   3. ORCID     — authoritative for researchers.
 *   4. WorldCat  — authoritative for bibliographic works.
 *
 * New projects should follow this convention by default: give every linkable
 * entity a Wikidata ID where one exists, and let the builder derive both the
 * `sameAs` array and the stable `@id` from it. Do not hand-write `@id` as a
 * node URL.
 */
interface SameAsLinkBuilderInterface {

  /**
   * Builds the @id and sameAs values for a set of authority identifiers.
   *
   * @param array $identifiers
   *   Authority identifiers keyed by authority machine name. Supported keys are
   *   'wikidata', 'viaf', 'orcid' and 'worldcat'. Each value may be a single
   *   identifier or an array of identifiers. Empty, whitespace-only or
   *   malformed identifiers are skipped. Examples:
   *   @code
   *   [
   *     'wikidata' => 'Q42',
   *     'viaf' => ['113230702', '75121530'],
   *     'orcid' => '0000-0002-1825-0097',
   *   ]
   *   @endcode
   *
   * @return array
   *   An array that may contain:
   *   - '@id': The stable canonical URI, derived per the @id-stability
   *     convention. Omitted when no valid identifier is given.
   *   - 'sameAs': A de-duplicated, order-stable list of authority URLs. Omitted
   *     when no valid identifier is given.
   *   Merge the result into the JSON-LD object it describes.
   */
  public function build(array $identifiers): array;

  /**
   * Builds the @id and sameAs values from an entity's fields.
   *
   * A thin convenience over ::build() that reads identifier strings from the
   * entity's fields. It intentionally takes no field names of its own: the
   * caller maps each authority to whatever field holds it, keeping the service
   * decoupled from any single project's field schema.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to read identifiers from.
   * @param array $field_map
   *   Maps an authority machine name (see ::build()) to the machine name of the
   *   field holding its identifier(s). Missing or empty fields are skipped. All
   *   values of multi-value fields are used. Example:
   *   @code
   *   ['wikidata' => 'field_wikidata_id', 'viaf' => 'field_viaf_id']
   *   @endcode
   *
   * @return array
   *   The same structure as ::build().
   */
  public function buildForEntity(FieldableEntityInterface $entity, array $field_map): array;

}
