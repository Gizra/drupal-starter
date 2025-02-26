<?php

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Disallows EntityInterface arguments in methods within ThemeTrait classes.
 *
 * This rule checks classes in the Drupal\server_general\ThemeTrait namespace
 * and ensures their methods don't accept EntityInterface parameters.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class NoEntityInterfaceInThemeTraitRule implements Rule
{
  private const ERROR_MESSAGE = "Methods in ThemeTrait classes cannot accept EntityInterface arguments.";
  private const THEME_TRAIT_NAMESPACE = 'Drupal\server_general\ThemeTrait';

  public function getNodeType(): string
  {
    return ClassMethod::class;
  }

  public function processNode(Node $node, Scope $scope): array
  {
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    if (!$this->isInThemeTraitNamespace($scope)) {
      return [];
    }

    $classReflection = $scope->getClassReflection();
    if ($classReflection === null) {
      return [];
    }

    $methodName = $node->name->toString();
    $methodReflection = $classReflection->getMethod($methodName, $scope);

    foreach ($methodReflection->getVariants() as $variant) {
      foreach ($variant->getParameters() as $param) {
        if ($this->isEntityInterfaceParameter($param)) {

          return [
            RuleErrorBuilder::message(self::ERROR_MESSAGE)
              ->line($node->getStartLine())
              ->build()
          ];
        }
      }
    }

    return [];
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
    return $class->implementsInterface(
      'Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface'
    );
  }

  private function isInThemeTraitNamespace(Scope $scope): bool
  {
    if (!$scope->isInTrait()) {
      return false;
    }

    $traitReflection = $scope->getTraitReflection();
    // @todo: Find a better way to check the namespace.
    return str_contains($traitReflection->getName(), '\ThemeTrait');
  }

  private function isEntityInterfaceParameter(\PHPStan\Reflection\ParameterReflection $param): bool
  {
    $paramType = $param->getType();

    if (!$paramType->isObject()->yes()) {
      return false;
    }

    $entityInterfaceType = new ObjectType('Drupal\Core\Entity\EntityInterface');
    return $entityInterfaceType->isSuperTypeOf($paramType)->yes();
  }
}
