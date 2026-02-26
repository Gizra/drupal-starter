<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Flags build* methods that load referenced entities without cache metadata.
 *
 * In pluggable_entity_view_builder EntityViewBuilder plugins, any method that
 * calls referencedEntities(), loadMultiple(), or loadByProperties() must also
 * use CacheableMetadata to track cache dependencies — unless the result is
 * passed to a safe delegating method (buildReferencedEntities, buildEntities,
 * etc.) or only immutable properties (bundle, id) are read.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class MissingCacheMetadataRule implements Rule {

  private const ERROR_MESSAGE = 'Method %s() calls %s() but does not add cache metadata via CacheableMetadata. Referenced entities whose field data is rendered must be tracked with addCacheableDependency() so the render cache is invalidated when they change.';

  /**
   * Methods that internally handle cache metadata for their entity loads.
   */
  private const SAFE_DELEGATING_METHODS = [
    'buildReferencedEntities',
    'buildEntities',
    'buildEntitiesWithViewModes',
    'buildReferencedEntitiesWithViewModes',
    'buildReferencedEntityLabelsPlainTextTags',
    'buildReferencedEntityLabelsButtonTags',
  ];

  /**
   * Entity loading methods we check for.
   */
  private const ENTITY_LOAD_METHODS = [
    'referencedEntities',
    'loadMultiple',
    'loadByProperties',
  ];

  /**
   * Methods on loaded entities that are immutable (no cache dep needed).
   */
  private const IMMUTABLE_ENTITY_METHODS = [
    'bundle',
    'id',
    'getEntityTypeId',
    'uuid',
  ];

  /**
   * Cache metadata methods that indicate proper handling.
   */
  private const CACHE_METHODS = [
    'addCacheableDependency',
    'addCacheTags',
    'createFromRenderArray',
  ];

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return ClassMethod::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    if (!$node instanceof ClassMethod) {
      return [];
    }

    // Only check build* methods in EntityViewBuilder classes.
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    $methodName = $node->name->toString();
    if (!str_starts_with($methodName, 'build')) {
      return [];
    }

    if ($node->stmts === NULL) {
      return [];
    }

    $nodeFinder = new NodeFinder();

    // Find all method calls in this method body.
    $allMethodCalls = $nodeFinder->findInstanceOf($node->stmts, MethodCall::class);

    // Check if any entity loading method is called.
    $entityLoadCalls = $this->findEntityLoadCalls($allMethodCalls);
    if (empty($entityLoadCalls)) {
      return [];
    }

    // Check if results are only passed to safe delegating methods.
    $hasSafeDelegation = $this->allLoadsAreSafeDelegated($allMethodCalls, $entityLoadCalls);
    if ($hasSafeDelegation) {
      return [];
    }

    // Check if only immutable properties are accessed on loaded entities.
    if ($this->onlyImmutableAccess($node, $entityLoadCalls, $nodeFinder)) {
      return [];
    }

    // Check if CacheableMetadata is used in this method.
    if ($this->hasCacheMetadata($allMethodCalls)) {
      return [];
    }

    // Build error for each uncovered entity load call.
    $errors = [];
    foreach ($entityLoadCalls as $call) {
      $loadMethodName = $call->name instanceof Identifier ? $call->name->toString() : 'unknown';
      $errors[] = RuleErrorBuilder::message(
        sprintf(self::ERROR_MESSAGE, $methodName, $loadMethodName)
      )
        ->line($call->getStartLine())
        ->addTip('Use CacheableMetadata::createFromRenderArray($build)->addCacheableDependency($entity)->applyTo($build) to track cache dependencies for loaded entities.')
        ->identifier('cacheMetadata.missingCacheMetadata')
        ->build();
    }

    return $errors;
  }

  /**
   * Checks if the current scope is within an EntityViewBuilder class.
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
   * Finds entity loading method calls from the list of all method calls.
   *
   * @param MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   *
   * @return MethodCall[]
   *   Entity loading method calls found.
   */
  private function findEntityLoadCalls(array $allMethodCalls): array {
    $entityLoadCalls = [];
    foreach ($allMethodCalls as $call) {
      if (!$call->name instanceof Identifier) {
        continue;
      }
      if (in_array($call->name->toString(), self::ENTITY_LOAD_METHODS, TRUE)) {
        $entityLoadCalls[] = $call;
      }
    }
    return $entityLoadCalls;
  }

  /**
   * Checks if all entity load calls are passed to safe delegating methods.
   *
   * A load is "safe delegated" if a safe method (e.g., buildEntities) is
   * also called in the same method. This is a heuristic — the safe method
   * call doesn't need to receive the exact same variable, but its presence
   * indicates the method follows the pattern.
   *
   * @param MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   * @param MethodCall[] $entityLoadCalls
   *   The entity load calls found.
   *
   * @return bool
   *   TRUE if all loads appear to be safely delegated.
   */
  private function allLoadsAreSafeDelegated(array $allMethodCalls, array $entityLoadCalls): bool {
    // Find safe delegating method calls.
    $safeCalls = [];
    foreach ($allMethodCalls as $call) {
      if (!$call->name instanceof Identifier) {
        continue;
      }
      if (in_array($call->name->toString(), self::SAFE_DELEGATING_METHODS, TRUE)) {
        $safeCalls[] = $call;
      }
    }

    if (empty($safeCalls)) {
      return FALSE;
    }

    // Each entity load call must have a corresponding safe call.
    // We match by checking that for each entity load, there is a safe call
    // that appears after it in the source (by line number).
    foreach ($entityLoadCalls as $loadCall) {
      $hasSafe = FALSE;
      foreach ($safeCalls as $safeCall) {
        if ($safeCall->getStartLine() >= $loadCall->getStartLine()) {
          $hasSafe = TRUE;
          break;
        }
      }
      if (!$hasSafe) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if loaded entities are only accessed for immutable properties.
   *
   * This checks whether the variable assigned from referencedEntities() is
   * only used in method calls to bundle(), id(), etc.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method
   *   The method node.
   * @param MethodCall[] $entityLoadCalls
   *   The entity load calls.
   * @param \PhpParser\NodeFinder $nodeFinder
   *   The node finder.
   *
   * @return bool
   *   TRUE if only immutable properties are accessed.
   */
  private function onlyImmutableAccess(ClassMethod $method, array $entityLoadCalls, NodeFinder $nodeFinder): bool {
    // For each entity load, find the variable it's assigned to. Then check
    // that every method call on that variable (or items iterated from it)
    // is in the immutable list.
    foreach ($entityLoadCalls as $loadCall) {
      $assignedVar = $this->findAssignedVariable($loadCall, $nodeFinder, $method);
      if ($assignedVar === NULL) {
        // Can't determine the variable — not safe to skip.
        return FALSE;
      }

      // Find all method calls in the method body that are called on this
      // variable or on loop variables derived from it.
      $allCalls = $nodeFinder->findInstanceOf($method->stmts, MethodCall::class);
      foreach ($allCalls as $call) {
        if (!$call->name instanceof Identifier) {
          continue;
        }
        $callName = $call->name->toString();

        // Skip the entity load call itself.
        if (in_array($callName, self::ENTITY_LOAD_METHODS, TRUE)) {
          continue;
        }

        // Skip cache-related methods.
        if (in_array($callName, self::CACHE_METHODS, TRUE)) {
          continue;
        }

        // Skip safe delegating methods.
        if (in_array($callName, self::SAFE_DELEGATING_METHODS, TRUE)) {
          continue;
        }

        // Check if this call is on the loaded variable or a derivative.
        if ($this->isCallOnVariable($call, $assignedVar)) {
          if (!in_array($callName, self::IMMUTABLE_ENTITY_METHODS, TRUE)) {
            // Non-immutable method call on the loaded entity.
            return FALSE;
          }
        }
      }
    }

    return TRUE;
  }

  /**
   * Finds the variable name that a method call result is assigned to.
   *
   * Looks for patterns like:
   *   $paragraphs = $field->referencedEntities();
   *
   * @return string|null
   *   The variable name, or NULL if not found.
   */
  private function findAssignedVariable(MethodCall $loadCall, NodeFinder $nodeFinder, ClassMethod $method): ?string {
    $assigns = $nodeFinder->findInstanceOf($method->stmts, Node\Expr\Assign::class);
    foreach ($assigns as $assign) {
      if (!$assign instanceof Node\Expr\Assign) {
        continue;
      }
      // Check if the right side of the assignment is our load call.
      if ($assign->expr === $loadCall && $assign->var instanceof Node\Expr\Variable) {
        $name = $assign->var->name;
        return is_string($name) ? $name : NULL;
      }
    }
    return NULL;
  }

  /**
   * Checks if a method call is made on a given variable or its loop items.
   *
   * Matches:
   *   $varName->method()
   *   $item->method() where $item comes from foreach($varName as $item)
   *
   * This is a heuristic — it checks variable names, not full data flow.
   */
  private function isCallOnVariable(MethodCall $call, string $varName): bool {
    if ($call->var instanceof Node\Expr\Variable) {
      $callVarName = $call->var->name;
      if (is_string($callVarName) && $callVarName === $varName) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if any CacheableMetadata methods are called in the method body.
   *
   * @param MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   *
   * @return bool
   *   TRUE if cache metadata methods are present.
   */
  private function hasCacheMetadata(array $allMethodCalls): bool {
    foreach ($allMethodCalls as $call) {
      if (!$call->name instanceof Identifier) {
        continue;
      }
      if (in_array($call->name->toString(), self::CACHE_METHODS, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
