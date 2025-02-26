<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Disallows EntityInterface arguments in methods of classes within ThemeTrait namespace.
 *
 * This rule checks classes in the Drupal\server_general\ThemeTrait namespace and ensures
 * that their methods don't accept EntityInterface parameters.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
class NoEntityInterfaceInThemeTraitRule implements Rule {

  /**
   * Error message for when EntityInterface is detected as a parameter.
   */
  private const ERROR_MESSAGE = "Methods in ThemeTrait classes cannot accept EntityInterface parameters. Only simple types (int, array, string, bool, Url, and Link) are allowed.";

  /**
   * Specifies that this rule processes class nodes.
   */
  public function getNodeType(): string {
    return Class_::class;
  }

  /**
   * Processes class nodes to check for EntityInterface parameters.
   *
   * @param Node $node The class node being analyzed.
   * @param Scope $scope The current analysis scope.
   *
   * @return array<int, \PHPStan\Rules\RuleError> An array of errors, empty if no issues are found.
   */
  public function processNode(Node $node, Scope $scope): array {
    // Ensure we're dealing with a class in the ThemeTrait namespace
    if (!$this->isInThemeTraitNamespace($scope)) {
      return [];
    }

    $errors = [];
    $classReflection = $scope->getClassReflection();

    if ($classReflection === null) {
      return [];
    }

    // Get all methods including inherited ones
    $methods = $classReflection->getNativeReflection()->getMethods();

    foreach ($methods as $method) {
      $parameters = $method->getParameters();

      foreach ($parameters as $parameter) {
        $paramType = $scope->getFunctionType(
          $parameter->getType(),
          false,
          false
        );

        if ($paramType instanceof ObjectType &&
          $paramType->isInstanceOf('Drupal\Core\Entity\EntityInterface')->yes()) {
          $errors[] = RuleErrorBuilder::message(self::ERROR_MESSAGE)
            ->line($node->getStartLine())
            ->identifier('themeTrait.noEntityInterface')
            ->tip('Use simple data types or extract entity data before passing to ThemeTrait methods.')
            ->build();
        }
      }
    }

    return $errors;
  }

  /**
   * Checks if the current scope is within the ThemeTrait namespace.
   *
   * @param Scope $scope The current analysis scope.
   *
   * @return bool TRUE if the scope is in ThemeTrait namespace, FALSE otherwise.
   */
  private function isInThemeTraitNamespace(Scope $scope): bool {
    $class = $scope->getClassReflection();
    if ($class === null) {
      return false;
    }

    $namespace = $class->getNativeReflection()->getNamespaceName();
    return str_starts_with($namespace, 'Drupal\server_general\ThemeTrait');
  }
}
