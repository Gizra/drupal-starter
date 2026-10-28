<?php

namespace Drupal\server_rollbar_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Rollbar error.
 */
final class RollbarError extends ControllerBase {

  /**
   * Get invalid data from a node.
   */
  protected function debug(NodeInterface $node, int $count = 0, array $data = []) {
    // Increase complexity by adding nested arrays and objects.
    if ($count < 500) {
      $new_data = [
        'int' => rand(1, PHP_INT_MAX),
        'nested_array' => array_fill(0, 10, str_repeat('x', 1000)),
        'node_label' => $node->label(),
        'object' => (object) [
          'id' => uniqid(),
          'timestamp' => time(),
          'random' => rand(1, PHP_INT_MAX),
          'deep_nested' => new \stdClass(),
        ],
      ];

      // Recurse to build deeper structure.
      return $this->debug($node, $count + 1, array_merge($data, [$new_data]));
    }

    // @phpstan-ignore-next-line
    return $node->label() . $f;
  }

  /**
   * Multiple errors in a row.
   */
  protected function try(int $count = 0) {
    // @phpstan-ignore-next-line
    $a['nonexisting_key'] = $b[$count];
    if ($count < 50) {
      $this->try($count + 1);
    }
  }

  /**
   * Try to reproduce Rollbar OOM error.
   */
  public function build() {
    // phpcs:disable
    // @phpstan-ignore-next-line
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
    // phpcs:enable
    $this->try();
    return [
      // @phpstan-ignore-next-line
      '#markup' => '<pre class="rollbar-error">' . $a . $this->debug($node) . '</pre>',
    ];
  }

}
