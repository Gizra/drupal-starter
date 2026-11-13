<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Custom;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Flags build*() methods loading entities without cache metadata.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class MissingCacheMetadataRule implements Rule {

  /**
   * Reusable node finder for AST traversal.
   */
  private NodeFinder $nodeFinder;

  public function __construct() {
    $this->nodeFinder = new NodeFinder();
  }

  private const ERROR_MESSAGE = 'Method %s() calls %s() but does not add cache metadata via CacheableMetadata. Referenced entities whose field data is rendered must be tracked with addCacheableDependency() so the render cache is invalidated when they change.';

  /**
   * PEVB methods that handle cache metadata internally.
   */
  private const SAFE_DELEGATING_METHODS = [
    'buildReferencedEntities',
    'buildEntities',
    'buildEntitiesWithViewModes',
    'buildReferencedEntitiesWithViewModes',
  ];

  /**
   * Entity loading methods that may need cache tracking.
   */
  private const ENTITY_LOAD_METHODS = [
    'referencedEntities',
    'load',
    'loadMultiple',
    'loadByProperties',
  ];

  /**
   * Entity methods returning immutable data (no cache dep needed).
   */
  private const IMMUTABLE_ENTITY_METHODS = [
    'bundle',
    'id',
    'getEntityTypeId',
    'uuid',
  ];

  /**
   * CacheableMetadata method calls that indicate proper cache handling.
   *
   * If any of these appear in the method body, we assume the developer is
   * handling cache metadata and don't flag the method.
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

    // Gate 1: Only check build*() methods in EntityViewBuilder plugin classes.
    // Other classes (services, controllers, etc.) are not our concern.
    if (!$this->isInEntityViewBuilder($scope)) {
      return [];
    }

    $methodName = $node->name->toString();
    if (!str_starts_with($methodName, 'build')) {
      return [];
    }

    // Abstract methods have no body to analyze.
    if ($node->stmts === NULL) {
      return [];
    }

    // Collect all method calls in this method's body (used by multiple checks).
    $allMethodCalls = $this->nodeFinder->findInstanceOf($node->stmts, MethodCall::class);

    // Gate 2: Does this method call any entity loading method?
    // If not, there's nothing to check — no entities loaded means no cache
    // dependencies to track.
    $entityLoadCalls = $this->findEntityLoadCalls($allMethodCalls);
    if (empty($entityLoadCalls)) {
      return [];
    }

    // Gate 3: Are all entity loads followed by a safe delegating method?
    // e.g. $entities = $field->referencedEntities();
    // $this->buildEntities($entities, ...);
    // buildEntities() handles cache metadata internally, so this is safe.
    if ($this->allLoadsAreSafeDelegated($allMethodCalls, $entityLoadCalls)) {
      return [];
    }

    // Gate 4: Only immutable access (id/bundle)? Skip.
    if ($this->onlyImmutableAccess($node, $entityLoadCalls)) {
      return [];
    }

    // Gate 5: Is CacheableMetadata already used in this method?
    // If the developer is calling addCacheableDependency/addCacheTags/
    // createFromRenderArray, they are already handling caching.
    if ($this->hasCacheMetadata($allMethodCalls)) {
      return [];
    }

    // All gates failed — this method loads entities, uses mutable data from
    // them, and doesn't track cache dependencies. Report an error.
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
   * Checks if the scope is a PEVB plugin class.
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
   * Finds entity loading calls among all method calls.
   *
   * @param \PhpParser\Node\Expr\MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   *
   * @return \PhpParser\Node\Expr\MethodCall[]
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
   * Checks if all loads are passed to safe delegating methods.
   *
   * @param \PhpParser\Node\Expr\MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   * @param \PhpParser\Node\Expr\MethodCall[] $entityLoadCalls
   *   The entity load calls found.
   *
   * @return bool
   *   TRUE if every load has a safe delegating call after it.
   */
  private function allLoadsAreSafeDelegated(array $allMethodCalls, array $entityLoadCalls): bool {
    // Collect all safe delegating method calls in the method.
    $safeCalls = [];
    foreach ($allMethodCalls as $call) {
      if (!$call->name instanceof Identifier) {
        continue;
      }
      if (in_array($call->name->toString(), self::SAFE_DELEGATING_METHODS, TRUE)) {
        $safeCalls[] = $call;
      }
    }

    // If there are no safe calls at all, delegation is impossible.
    if (empty($safeCalls)) {
      return FALSE;
    }

    // Every entity load must have at least one safe call on the same line or
    // after it. If any load lacks a subsequent safe call, delegation is
    // incomplete.
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
   * Checks if loaded entities only use immutable accessors.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method
   *   The method node.
   * @param \PhpParser\Node\Expr\MethodCall[] $entityLoadCalls
   *   The entity load calls.
   *
   * @return bool
   *   TRUE if only immutable properties are accessed.
   */
  private function onlyImmutableAccess(ClassMethod $method, array $entityLoadCalls): bool {
    foreach ($entityLoadCalls as $loadCall) {
      // Step 1: Find what variable the load result is assigned to.
      // e.g. "$tags = $field->referencedEntities();" → $assignedVar = "tags".
      $assignedVar = $this->findAssignedVariable($loadCall, $method);
      if ($assignedVar === NULL) {
        // If the result isn't assigned to a variable we can track (e.g. it's
        // used inline or in a complex expression), we can't determine safety
        // so we conservatively say "not immutable-only".
        return FALSE;
      }

      // Also track foreach loop variables derived from the collection.
      $trackedVars = [$assignedVar];
      $foreachNodes = $this->nodeFinder->findInstanceOf($method->stmts, Foreach_::class);
      foreach ($foreachNodes as $foreach) {
        if (!($foreach->expr instanceof Variable) || !is_string($foreach->expr->name)) {
          continue;
        }
        if (!($foreach->valueVar instanceof Variable) || !is_string($foreach->valueVar->name)) {
          continue;
        }
        if ($foreach->expr->name !== $assignedVar) {
          continue;
        }
        // This foreach iterates over our loaded collection, so its value
        // variable holds individual loaded entities.
        $trackedVars[] = $foreach->valueVar->name;
      }

      // Step 3: Scan all method calls in the body. For any call on a tracked
      // variable, check if it's a non-immutable method.
      $allCalls = $this->nodeFinder->findInstanceOf($method->stmts, MethodCall::class);
      foreach ($allCalls as $call) {
        if (!$call->name instanceof Identifier) {
          continue;
        }
        $callName = $call->name->toString();

        // Skip infrastructure calls (entity loading, cache methods, safe
        // delegation) — these are not "access" on the loaded entity's data.
        if (in_array($callName, self::ENTITY_LOAD_METHODS, TRUE)) {
          continue;
        }
        if (in_array($callName, self::CACHE_METHODS, TRUE)) {
          continue;
        }
        if (in_array($callName, self::SAFE_DELEGATING_METHODS, TRUE)) {
          continue;
        }

        // Check if this call is on one of our tracked variables (the assigned
        // collection variable or a foreach loop variable derived from it).
        if ($this->isCallOnVariables($call, $trackedVars)) {
          if (!in_array($callName, self::IMMUTABLE_ENTITY_METHODS, TRUE)) {
            // A non-immutable method (e.g. label(), getTitle(), get()) is
            // called on a loaded entity → the rendered output depends on
            // mutable entity data → cache dependency IS needed.
            return FALSE;
          }
        }
      }
    }

    // All entity load results are only accessed via immutable methods.
    return TRUE;
  }

  /**
   * Finds the variable a load call result is assigned to.
   *
   * @return string|null
   *   The variable name, or NULL if not a simple assignment.
   */
  private function findAssignedVariable(MethodCall $loadCall, ClassMethod $method): ?string {
    $assigns = $this->nodeFinder->findInstanceOf($method->stmts, Assign::class);
    foreach ($assigns as $assign) {
      if (!$assign instanceof Assign) {
        continue;
      }
      // Match: the right side of the assignment is our exact load call node.
      if ($assign->expr === $loadCall && $assign->var instanceof Variable) {
        $name = $assign->var->name;
        return is_string($name) ? $name : NULL;
      }
    }
    return NULL;
  }

  /**
   * Checks if a call targets any of the tracked variables.
   *
   * @param \PhpParser\Node\Expr\MethodCall $call
   *   The method call to check.
   * @param string[] $varNames
   *   Variable names to match against.
   *
   * @return bool
   *   TRUE if the call is on a tracked variable.
   */
  private function isCallOnVariables(MethodCall $call, array $varNames): bool {
    if ($call->var instanceof Variable) {
      $callVarName = $call->var->name;
      if (is_string($callVarName) && in_array($callVarName, $varNames, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if any CacheableMetadata methods are present.
   *
   * @param \PhpParser\Node\Expr\MethodCall[] $allMethodCalls
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
