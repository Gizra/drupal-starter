<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Custom;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Scalar\String_;

/**
 * Disallows the use of '#theme' directly in EntityViewBuilder classes.
 *
 * This rule checks for the presence of '#theme' keys in arrays and assignments
 * within classes
 * that implement the Drupal\pluggable_entity_view_builder\EntityViewBuilder\
 * EntityViewBuilderPluginInterface,
 * and reports an error if found, unless the usage is within a trait.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node>
 */
class NoThemeInEntityViewBuilderRule implements Rule {

  /**
   * Error message for when '#theme' is detected.
   */
  private const ERROR_MESSAGE = "The use of '#theme' is not allowed in PEVB's EntityViewBuilder classes. Instead, transfer the theming components into a ThemeTrait.";

  /**
   * Process all nodes to catch both array literals and assignments.
   */
  public function getNodeType(): string {
    return Node::class;
  }

  /**
   * Processes nodes to check for '#theme' usage.
   *
   * @param \PhpParser\Node $node
   *   The node being analyzed.
   * @param \PHPStan\Analyser\Scope $scope
   *   The current analysis scope.
   *
   * @return array<int, \PHPStan\Rules\RuleError>
   *   An array of errors, empty if no issues are found.
   */
  public function processNode(Node $node, Scope $scope): array {
    // Only proceed if we're in an EntityViewBuilder class.
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    // Allow '#theme' if this code is within a trait.
    if ($scope->isInTrait()) {
      return [];
    }

    $shouldReport = FALSE;

    if ($node instanceof Array_) {
      if ($this->checkArrayLiteral($node)) {
        $shouldReport = TRUE;
      }
    }
    elseif ($node instanceof Assign) {
      if ($this->checkAssignment($node)) {
        $shouldReport = TRUE;
      }
    }

    if (!$shouldReport) {
      return [];
    }

    return [
      RuleErrorBuilder::message(self::ERROR_MESSAGE)
        ->line($node->getStartLine())
        ->addTip('PEVB are meant to extract the dynamic data from the entity and pass it to the ThemeTrait traits. Like that we can call it from the Style guide, without needing to mock entities.')
        ->identifier('themeTrait.NoThemeInEntityViewBuilderRule')
        ->build(),
    ];
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
  private function isInEntityViewBuilder(Scope $scope): bool {
    $class = $scope->getClassReflection();
    if ($class === NULL) {
      return FALSE;
    }
    return $class->implementsInterface(
      'Drupal\pluggable_entity_view_builder\EntityViewBuilder\EntityViewBuilderPluginInterface'
    );
  }

  /**
   * Helper to check if an array literal contains a '#theme' key.
   *
   * $element = ['#theme' => 'foo'];
   *
   * @param \PhpParser\Node\Expr\Array_ $node
   *   The array node to inspect.
   *
   * @return bool
   *   TRUE if '#theme' is found, FALSE otherwise.
   */
  private function checkArrayLiteral(Array_ $node): bool {
    foreach ($node->items as $item) {
      if ($item !== NULL && $item->key instanceof String_ && $item->key->value === '#theme') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Helper to check if an assignment node assigns a value to a '#theme' key.
   *
   * $element['#theme'] = 'foo';
   *
   * @param \PhpParser\Node\Expr\Assign $node
   *   The assignment node to inspect.
   *
   * @return bool
   *   TRUE if assignment targets '#theme', FALSE otherwise.
   */
  private function checkAssignment(Assign $node): bool {
    if ($node->var instanceof ArrayDimFetch) {
      $dim = $node->var->dim;
      return ($dim instanceof String_ && $dim->value === '#theme');
    }
    return FALSE;
  }

}
