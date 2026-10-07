<?php

declare(strict_types=1);

namespace Drupal\server_general\JsonLd;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Default implementation of the sameAs link builder.
 *
 * See the interface for the @id-stability convention this service enforces.
 */
class SameAsLinkBuilder implements SameAsLinkBuilderInterface {

  /**
   * Supported authorities, in @id-priority order.
   *
   * The array order is significant: the first authority that yields a valid
   * identifier provides the stable `@id`. Each entry defines:
   * - 'url': The URL prefix an identifier is appended to.
   * - 'pattern': A regular expression the raw identifier must match. Guards
   *   against emitting broken URLs from malformed input; an identifier that
   *   fails the pattern is skipped.
   */
  protected const AUTHORITIES = [
    'wikidata' => [
      'url' => 'https://www.wikidata.org/wiki/',
      'pattern' => '/^Q[1-9][0-9]*$/',
    ],
    'viaf' => [
      'url' => 'https://viaf.org/viaf/',
      'pattern' => '/^[1-9][0-9]*$/',
    ],
    'orcid' => [
      'url' => 'https://orcid.org/',
      'pattern' => '/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X]$/',
    ],
    'worldcat' => [
      'url' => 'https://search.worldcat.org/title/',
      'pattern' => '/^[1-9][0-9]*$/',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build(array $identifiers): array {
    $same_as = [];
    $stable_id = NULL;

    // Iterate over the authorities in priority order, not over the caller's
    // input order, so the derived @id is deterministic regardless of how the
    // identifiers were passed in.
    foreach (self::AUTHORITIES as $authority => $info) {
      if (empty($identifiers[$authority])) {
        continue;
      }

      foreach ((array) $identifiers[$authority] as $raw_id) {
        $id = trim((string) $raw_id);
        if ($id === '' || !preg_match($info['pattern'], $id)) {
          continue;
        }

        $url = $info['url'] . $id;
        $same_as[] = $url;

        // The first valid identifier in priority order defines the @id.
        $stable_id ??= $url;
      }
    }

    $result = [];
    if ($stable_id !== NULL) {
      $result['@id'] = $stable_id;
    }
    if (!empty($same_as)) {
      $result['sameAs'] = array_values(array_unique($same_as));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForEntity(FieldableEntityInterface $entity, array $field_map): array {
    $identifiers = [];

    foreach ($field_map as $authority => $field_name) {
      if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        continue;
      }

      foreach ($entity->get($field_name)->getValue() as $item) {
        // Support both plain value fields and link fields.
        $value = $item['value'] ?? $item['uri'] ?? '';
        if ($value !== '') {
          $identifiers[$authority][] = $value;
        }
      }
    }

    return $this->build($identifiers);
  }

}
