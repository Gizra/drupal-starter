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
use PHPStan\Type\Type;

/**
 * Disallows EntityInterface arguments in methods of classes within ThemeTrait namespaces.
 *
 * This rule checks classes in any namespace ending with 'ThemeTrait' and ensures
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
    // Ensure we're dealing with a class in a ThemeTrait namespace
    if (!$this->isInThemeTraitNamespace($scope)) {
      return [];
    }

    $classReflection = $scope->getClassReflection();

    if ($classReflection === null) {
      return [];
    }

    // Get all methods including inherited ones
    $methods = $classReflection->getNativeReflection()->getMethods();

    foreach ($methods as $method) {
      $parameters = $method->getParameters();

      foreach ($parameters as $parameter) {
        // Get the PHPStan type from the parameter
        $paramReflection = $classReflection->getNativeMethod($method->getName())->getVariants()[0]->getParameters()[$parameter->getPosition()];
        $paramType = $paramReflection->getType();

        // Check if the type is an object and implements EntityInterface
        if ($paramType->isObject()->yes() &&
          $paramType->isSuperTypeOf(new \PHPStan\Type\ObjectType('Drupal\Core\Entity\EntityInterface'))->yes()) {
          return [
            RuleErrorBuilder::message(self::ERROR_MESSAGE)
              ->line($node->getStartLine())
              ->identifier('themeTrait.noEntityInterface')
              ->tip('Use simple data types or extract entity data before passing to ThemeTrait methods.')
              ->build()
          ];
        }
      }
    }

    return [];
  }

  /**
   * Checks if the current scope is within a namespace ending with ThemeTrait.
   *
   * @param Scope $scope The current analysis scope.
   *
   * @return bool TRUE if the namespace ends with 'ThemeTrait', FALSE otherwise.
   */
  private function isInThemeTraitNamespace(Scope $scope): bool {
    $class = $scope->getClassReflection();
    if ($class === null) {
      return false;
    }

    $namespace = $class->getNativeReflection()->getNamespaceName();
    return str_ends_with($namespace, '\ThemeTrait');
  }
}
