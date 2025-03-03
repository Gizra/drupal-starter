<?php

declare(strict_types=1);

namespace Drupal\server_general\Stan;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

/**
 * Checks if #theme is used in EntityViewBuilder classes.
 *
 * @implements Rule<Array_>
 */
class NoThemeInEntityViewBuilderRule implements Rule {
  private const ERROR_MESSAGE = "Use of '#theme' is not allowed in Pluggable entity view builder `EntityViewBuilder` classes";

  /**
   * Gets the node type.
   */
  public function getNodeType(): string {
    return Array_::class;
  }

  /**
   * Processes the node.
   */
  public function processNode(Node $node, Scope $scope): array {
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    if ($this->containsThemeKey($node)) {
      return [
        RuleErrorBuilder::message(self::ERROR_MESSAGE)
          ->line($node->getStartLine())
          ->build(),
      ];
    }

    return [];
  }

  /**
   * Checks if class is extended from EntityViewBuilder.
   */
  private function isInEntityViewBuilder(Scope $scope): bool {
    $class = $scope->getClassReflection();
    if ($class === NULL) {
      return FALSE;
    }

    return $this->isEntityViewBuilder($class);
  }

  /**
   * Checks if class is subclass of EntityViewBuilder.
   */
  private function isEntityViewBuilder(ClassReflection $class): bool {
    return $class->isSubclassOf('Drupal\pluggable_entity_view_builder\EntityViewBuilder') ||
      $class->getName() === 'Drupal\pluggable_entity_view_builder\EntityViewBuilder';
  }

  /**
   * Checks if array contains '#theme' key.
   */
  private function containsThemeKey(Array_ $node): bool {
    foreach ($node->items as $item) {
      if ($item == NULL) {
        continue;
      }

      if ($item->key instanceof String_ && $item->key->value === '#theme') {
        return TRUE;
      }
    }

    return FALSE;
  }

}
