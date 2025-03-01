<?php

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Disallows EntityInterface arguments in methods within ThemeTrait classes.
 *
 * This rule checks classes in the `\ThemeTrait` namespace
 * and ensures their methods don't accept EntityInterface parameters.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class NoEntityInterfaceInThemeTraitRule implements Rule {
  private const ERROR_MESSAGE = "Methods in ThemeTrait classes cannot accept EntityInterface arguments.";

  public function getNodeType(): string {
    return ClassMethod::class;
  }

  public function processNode(Node $node, Scope $scope): array {
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
              ->addTip('Instead of passing an EntityInterface, since this is a ThemeTrait, you should pass only simple objects: int, bool, string, array, Stringable, TranslatableMarkup, Url and Link.')
              ->identifier('themeTrait.noEntityInterfaceInThemeTrait')
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
  private function isInEntityViewBuilder(Scope $scope): bool {
    $class = $scope->getClassReflection();
    if ($class === NULL) {
      return FALSE;
    }
    return $class->implementsInterface(
      'Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface'
    );
  }

  private function isInThemeTraitNamespace(Scope $scope): bool {
    if (!$scope->isInTrait()) {
      return FALSE;
    }

    $traitReflection = $scope->getTraitReflection();
    return str_contains($traitReflection->getName(), '\ThemeTrait');
  }

  private function isEntityInterfaceParameter(ParameterReflection $param): bool {
    $paramType = $param->getType();
    if (!$paramType->isObject()->yes()) {
      return FALSE;
    }

    $entityInterfaceType = new ObjectType('Drupal\Core\Entity\EntityInterface');
    return $entityInterfaceType->isSuperTypeOf($paramType)->yes();
  }
}
