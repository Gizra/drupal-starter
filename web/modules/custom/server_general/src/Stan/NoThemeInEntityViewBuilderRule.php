<?php

declare(strict_types=1);

namespace Drupal\server_general\Stan;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Array_>
 */
class NoThemeInEntityViewBuilderRule implements Rule
{
  private const ERROR_MESSAGE = "Use of '#theme' is not allowed in Pluggable entity view builder `EntityViewBuilder` classes";

  public function getNodeType(): string
  {
    return Array_::class;
  }

  public function processNode(Node $node, Scope $scope): array
  {
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    if ($this->containsThemeKey($node)) {
      return [
        RuleErrorBuilder::message(self::ERROR_MESSAGE)
          ->line($node->getLine())
          ->build()
      ];
    }

    return [];
  }

  private function isInEntityViewBuilder(Scope $scope): bool
  {
    $class = $scope->getClassReflection();
    if ($class === null) {
      return false;
    }

    return $this->isEntityViewBuilder($class);
  }

  private function isEntityViewBuilder(ClassReflection $class): bool
  {
    return $class->isSubclassOf('Drupal\pluggable_entity_view_builder\EntityViewBuilder') ||
      $class->getName() === 'Drupal\pluggable_entity_view_builder\EntityViewBuilder';
  }

  private function containsThemeKey(Array_ $node): bool
  {
    foreach ($node->items as $item) {
      if ($item === null) {
        continue;
      }

      if ($item->key instanceof Node\Scalar\String_ && $item->key->value === '#theme') {
        return true;
      }
    }

    return false;
  }
}
