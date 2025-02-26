<?php

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Disallows the use of '#theme' directly in EntityViewBuilder classes.
 *
 * This rule checks for the presence of '#theme' keys in arrays and assignments within classes
 * that implement Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface,
 * and reports an error if found, unless the usage is within a trait.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node>
 */
class NoThemeInEntityViewBuilderRule implements Rule
{
  /**
   * Error message for when '#theme' is detected.
   */
  private const ERROR_MESSAGE = "Use of '#theme' is not allowed in EntityViewBuilder classes";

  /**
   * Process all nodes to catch both array literals and assignments.
   */
  public function getNodeType(): string
  {
    return Node::class;
  }

  /**
   * Processes nodes to check for '#theme' usage.
   *
   * @param Node $node The node being analyzed.
   * @param Scope $scope The current analysis scope.
   *
   * @return array<int, \PHPStan\Rules\RuleError> An array of errors, empty if no issues are found.
   */
  public function processNode(Node $node, Scope $scope): array
  {
    // Only proceed if we're in an EntityViewBuilder class.
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    // Allow '#theme' if this code is within a trait.
    if ($scope->isInTrait()) {
      return [];
    }

    $errors = [];

    if ($node instanceof Array_) {
      if ($arrayErrors = $this->checkArrayLiteral($node)) {
        $errors = array_merge($errors, $arrayErrors);
      }
    }

    if ($node instanceof Assign) {
      if ($assignmentErrors = $this->checkAssignment($node)) {
        $errors = array_merge($errors, $assignmentErrors);
      }
    }

    return $errors;
  }

  /**
   * Checks if the current scope is within an EntityViewBuilder class.
   *
   * @param Scope $scope The current analysis scope.
   *
   * @return bool TRUE if the scope is an EntityViewBuilder class, FALSE otherwise.
   */
  private function isInEntityViewBuilder(Scope $scope): bool
  {
    $class = $scope->getClassReflection();
    if ($class === null) {
      return false;
    }
    return $this->isEntityViewBuilder($class);
  }

  /**
   * Determines if a class is an EntityViewBuilder.
   *
   * @param ClassReflection $class The class reflection to check.
   *
   * @return bool TRUE if the class implements EntityViewBuilderPluginInterface, FALSE otherwise.
   */
  private function isEntityViewBuilder(ClassReflection $class): bool
  {
    return $class->implementsInterface('Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface');
  }

  /**
   * Helper to check if an array literal contains a '#theme' key.
   *
   * @param Array_ $node The array node to inspect.
   *
   * @return array<int, \PHPStan\Rules\RuleError>|null Returns an error array if found, or null otherwise.
   */
  private function checkArrayLiteral(Array_ $node): ?array
  {
    if ($this->containsThemeKey($node)) {
      return [
        RuleErrorBuilder::message(self::ERROR_MESSAGE)
          ->line($node->getStartLine())
          ->build(),
      ];
    }
    return null;
  }

  /**
   * Helper to check if an assignment node assigns a value to a '#theme' key.
   *
   * @param Assign $node The assignment node to inspect.
   *
   * @return array<int, \PHPStan\Rules\RuleError>|null Returns an error array if found, or null otherwise.
   */
  private function checkAssignment(Assign $node): ?array
  {
    if ($node->var instanceof ArrayDimFetch) {
      $dim = $node->var->dim;
      if ($dim instanceof Node\Scalar\String_ && $dim->value === '#theme') {
        return [
          RuleErrorBuilder::message(self::ERROR_MESSAGE)
            ->line($node->getStartLine())
            ->build(),
        ];
      }
    }
    return null;
  }

  /**
   * Checks if an array literal contains a '#theme' key.
   *
   * @param Array_ $node The array node to inspect.
   *
   * @return bool TRUE if '#theme' is found, FALSE otherwise.
   */
  private function containsThemeKey(Array_ $node): bool
  {
    foreach ($node->items as $item) {
      if ($item !== null && $item->key instanceof Node\Scalar\String_ && $item->key->value === '#theme') {
        return true;
      }
    }
    return false;
  }
}
