<?php

declare(strict_types=1);

namespace Drupal\server_ai;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Builds the "resource card" payload shared by search and saved sessions.
 *
 * The /ai-search chat shows news matches as cards. The same shape is
 * produced two ways: live from a `search_resources` hit (snippet + score come
 * from the search index) and on reload from a saved session's referenced nodes
 * (no search context, so the snippet is derived from the node and score is 0).
 * Centralising the title/image/snippet logic keeps both paths in sync.
 */
final class CardBuilder {

  /**
   * Max length of a derived snippet, in characters.
   */
  private const SNIPPET_MAX_LEN = 300;

  /**
   * Builds a card array for one news node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The news node.
   * @param string|null $snippet
   *   A search-supplied snippet, or NULL to derive one from the node.
   * @param float $score
   *   The similarity score (0 when reloaded from a saved session).
   *
   * @return array{nid: int, uuid: string, title: string, snippet: string, score: float, imageUrl: string}
   *   The card payload the Elm app renders.
   */
  public function build(NodeInterface $node, ?string $snippet = NULL, float $score = 0.0): array {
    return [
      'nid' => (int) $node->id(),
      'uuid' => (string) $node->uuid(),
      'title' => (string) $node->label(),
      'snippet' => $snippet ?? $this->nodeSnippet($node),
      'score' => $score,
      'imageUrl' => $this->imageUrl($node),
    ];
  }

  /**
   * Truncates raw text to a snippet (shared with search-supplied content).
   */
  public function truncate(string $raw): string {
    $raw = trim($raw);
    if ($raw === '' || mb_strlen($raw) <= self::SNIPPET_MAX_LEN) {
      return $raw;
    }
    return rtrim(mb_substr($raw, 0, self::SNIPPET_MAX_LEN), " \t\n\r\0\x0B.,;:") . '…';
  }

  /**
   * Resolves a preview image URL for a news card.
   *
   * Returns an empty string when the node has no usable media reference so the
   * Elm card can fall back gracefully.
   */
  public function imageUrl(NodeInterface $node): string {
    foreach (['field_featured_image'] as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $media = $node->get($field_name)->entity;
      if (!$media instanceof MediaInterface) {
        continue;
      }
      $url = $this->mediaFileUrl($media);
      if (!empty($url)) {
        return $url;
      }
    }
    return '';
  }

  /**
   * Derives a snippet from the node's own text when search supplied none.
   */
  private function nodeSnippet(NodeInterface $node): string {
    foreach (['field_body'] as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $text = trim(strip_tags((string) $node->get($field_name)->value));
      if ($text !== '') {
        return $this->truncate($text);
      }
    }
    return '';
  }

  /**
   * Pulls a file URL out of a media entity.
   *
   * Walks the media's source field and falls back to the thumbnail file
   * reference (which every media entity carries) for non-image bundles.
   */
  private function mediaFileUrl(MediaInterface $media): string {
    $source = $media->getSource();
    $source_field = $source ? $source->getConfiguration()['source_field'] ?? '' : '';
    foreach (array_filter([$source_field, 'thumbnail']) as $field_name) {
      if (!$media->hasField($field_name) || $media->get($field_name)->isEmpty()) {
        continue;
      }
      $file = $media->get($field_name)->entity;
      if ($file instanceof FileInterface) {
        // Relative URL only — the browser resolves it against the page origin.
        return $file->createFileUrl(TRUE);
      }
    }
    return '';
  }

}
