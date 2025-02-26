<?php

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Disallows the use of '#theme' directly in EntityViewBuilder classes.
 *
 * This rule checks for the presence of '#theme' keys in arrays within classes
 * that extend Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface
 * and reports an error if found, unless the usage is within a trait.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Array_>
 */
class NoThemeInEntityViewBuilderRule implements Rule
{
  /**
   * Error message for when '#theme' is detected.
   */
  private const ERROR_MESSAGE = "Use of '#theme' is not allowed in EntityViewBuilder classes";

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string
  {
    return Array_::class;
  }

  /**
   * Processes array nodes to check for '#theme' usage.
   *
   * @param \PhpParser\Node $node
   *   The node being analyzed (an array).
   * @param \PHPStan\Analyser\Scope $scope
   *   The current analysis scope.
   *
   * @return array<int, \PHPStan\Rules\RuleError>
   *   An array of errors, empty if no issues are found.
   */
  public function processNode(Node $node, Scope $scope): array
  {
    // Ensure node is an Array_ instance.
    if (!$node instanceof Array_) {
      return [];
    }

    // Check if we're in an EntityViewBuilder class.
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    // Allow '#theme' if this code is within a trait.
    if ($scope->isInTrait()) {
      return [];
    }

    // Disallow '#theme' if directly in the EntityViewBuilder class.
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
   * Checks if the current scope is within an EntityViewBuilder class.
   *
   * @param \PHPStan\Analyser\Scope $scope
   *   The current analysis scope.
   *
   * @return bool
   *   TRUE if the scope is an EntityViewBuilder class, FALSE otherwise.
   */
  private function isInEntityViewBuilder(Scope $scope): bool
  {
    $class = $scope->getClassReflection();
    if ($class === NULL) {
      return FALSE;
    }

    return $this->isEntityViewBuilder($class);
  }

  /**
   * Determines if a class is an EntityViewBuilder.
   *
   * @param \PHPStan\Reflection\ClassReflection $class
   *   The class reflection to check.
   *
   * @return bool
   *   TRUE if the class extends EntityViewBuilderPluginInterface, FALSE otherwise.
   */
  private function isEntityViewBuilder(ClassReflection $class): bool
  {
    return $class->isSubclassOf('Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface') ||
      $class->getName() === 'Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface';
  }

  /**
   * Checks if an array contains a '#theme' key.
   *
   * @param \PhpParser\Node\Expr\Array_ $node
   *   The array node to inspect.
   *
   * @return bool
   *   TRUE if '#theme' is found, FALSE otherwise.
   */
  private function containsThemeKey(Array_ $node): bool
  {
    foreach ($node->items as $item) {
      if ($item->key instanceof Node\Scalar\String_ && $item->key->value === '#theme') {
        return TRUE;
      }
    }

    return FALSE;
  }
}
