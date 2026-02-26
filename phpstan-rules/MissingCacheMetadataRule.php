<?php

declare(strict_types=1);

namespace Drupal\PHPStan\Custom;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Flags build* methods that load entities without adding cache metadata.
 *
 * WHY THIS RULE EXISTS:
 * In Drupal's render cache system, every render array must declare its cache
 * dependencies. When an EntityViewBuilder plugin loads referenced entities
 * (e.g. via referencedEntities()) and renders their field data (title, image,
 * body, etc.), the render array must track those entities as cache
 * dependencies. Without this, Drupal's render cache won't know to invalidate
 * when those referenced entities change — leading to stale output.
 *
 * WHAT IT CHECKS:
 * This rule scans build*() methods in classes implementing
 * EntityViewBuilderPluginInterface. It looks for calls to entity-loading
 * methods (referencedEntities, loadMultiple, loadByProperties) and reports
 * an error if CacheableMetadata is not used in the same method.
 *
 * WHAT IT SKIPS (to avoid false positives):
 * 1. Safe delegating methods — methods like buildReferencedEntities() and
 *    buildEntities() that go through Drupal's entity view builder, which
 *    automatically handles cache metadata internally.
 * 2. Immutable-only access — when loaded entities are only used to read
 *    properties that never change after creation (bundle, id, uuid), no
 *    cache dependency is needed because the data can't become stale.
 * 3. Methods already using CacheableMetadata — if addCacheableDependency,
 *    addCacheTags, or createFromRenderArray is called, the developer is
 *    already handling caching.
 *
 * DESIGN PRINCIPLE:
 * This rule is intentionally conservative — it only flags cases where we are
 * confident caching is missing. It may miss some edge cases (false negatives)
 * but should never produce false positives. This makes it safe to run in CI.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class MissingCacheMetadataRule implements Rule {

  private const ERROR_MESSAGE = 'Method %s() calls %s() but does not add cache metadata via CacheableMetadata. Referenced entities whose field data is rendered must be tracked with addCacheableDependency() so the render cache is invalidated when they change.';

  /**
   * Methods that internally handle cache metadata for their entity loads.
   *
   * These are PEVB helper methods that go through Drupal's entity view builder
   * or already use CacheableMetadata internally. When a build*() method calls
   * one of these, the cache metadata is handled — no manual tracking needed.
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
   *
   * These are the methods that retrieve entities from the database. If a
   * build*() method calls one of these, the loaded entities may need to be
   * tracked as cache dependencies.
   */
  private const ENTITY_LOAD_METHODS = [
    'referencedEntities',
    'loadMultiple',
    'loadByProperties',
  ];

  /**
   * Methods on entities that return immutable data (no cache dep needed).
   *
   * These properties are set at entity creation and never change. Reading
   * only these values from a loaded entity is safe without cache tracking,
   * because the rendered output can never become stale.
   *
   * Example of safe code (no CacheableMetadata needed):
   *   $tags = $field->referencedEntities();
   *   foreach ($tags as $tag) {
   *     $ids[] = $tag->id();  // id() never changes — safe.
   *   }
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
   * Main analysis: checks a single build*() method for missing cache metadata.
   *
   * The logic flows through a series of checks, each of which can "clear" the
   * method (return no errors). Only if all checks fail do we report an error:
   *
   * 1. Is this a build*() method in an EntityViewBuilder? (skip if not)
   * 2. Does it call any entity loading method? (skip if not)
   * 3. Are all loads passed to safe delegating methods? (skip if yes)
   * 4. Are loaded entities only accessed for immutable props? (skip if yes)
   * 5. Is CacheableMetadata already used? (skip if yes)
   * 6. If none of the above → report error.
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

    $nodeFinder = new NodeFinder();

    // Collect all method calls in this method's body (used by multiple checks).
    $allMethodCalls = $nodeFinder->findInstanceOf($node->stmts, MethodCall::class);

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

    // Gate 4: Are loaded entities only used for immutable property reads?
    // e.g. $tags = $field->referencedEntities();
    // foreach ($tags as $tag) { $ids[] = $tag->id(); }
    // Reading id()/bundle() never produces stale output,
    // so no cache dep needed.
    if ($this->onlyImmutableAccess($node, $entityLoadCalls, $nodeFinder)) {
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
   * Checks if the current scope is within an EntityViewBuilder plugin class.
   *
   * We only care about classes implementing EntityViewBuilderPluginInterface,
   * which is the PEVB interface for entity view builder plugins. These are the
   * classes responsible for building render arrays from entities.
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
   * Scans for referencedEntities(), loadMultiple(), and loadByProperties().
   * These are the entry points where entities come into the method from the
   * database and may need cache dependency tracking.
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
   * Checks if all entity loads are followed by safe delegating method calls.
   *
   * This is a heuristic based on line numbers, not data flow. For each entity
   * load call, we check whether a safe delegating method (e.g. buildEntities)
   * appears on the same line or later. The reasoning: in the PEVB pattern,
   * entities are loaded and then immediately passed to a builder method.
   *
   * Example that passes (safe — buildEntities handles caching):
   *   $items = $field->referencedEntities();          // line 30
   *   $build['items'] = $this->buildEntities($items); // line 31
   *
   * Example that fails (no safe delegation after the load):
   *   $items = $field->referencedEntities();           // line 30
   *   $build['titles'] = array_map(fn($i) => $i->label(), $items); // line 31
   *
   * @param \PhpParser\Node\Expr\MethodCall[] $allMethodCalls
   *   All method calls in the method body.
   * @param \PhpParser\Node\Expr\MethodCall[] $entityLoadCalls
   *   The entity load calls found.
   *
   * @return bool
   *   TRUE if every entity load has a safe delegating call after it.
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
   * Checks if loaded entities are only accessed for immutable properties.
   *
   * When entities are loaded but only id(), bundle(), uuid(), or
   * getEntityTypeId() is called on them, the rendered output cannot become
   * stale (these values never change), so no cache dependency is needed.
   *
   * This method tracks variables through two patterns:
   *
   * 1. Direct variable access:
   *      $entity = $storage->loadMultiple($ids);
   *      $entity->bundle();  // ← checked
   *
   * 2. Foreach loop iteration:
   *      $tags = $field->referencedEntities();
   *      foreach ($tags as $tag) {
   *        $tag->label();  // ← also checked (derived from $tags)
   *      }
   *
   * If ANY non-immutable method is called on the loaded variable or its
   * foreach derivatives, this returns FALSE (not safe to skip).
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method
   *   The method node.
   * @param \PhpParser\Node\Expr\MethodCall[] $entityLoadCalls
   *   The entity load calls.
   * @param \PhpParser\NodeFinder $nodeFinder
   *   The node finder.
   *
   * @return bool
   *   TRUE if only immutable properties are accessed on all loaded entities.
   */
  private function onlyImmutableAccess(ClassMethod $method, array $entityLoadCalls, NodeFinder $nodeFinder): bool {
    foreach ($entityLoadCalls as $loadCall) {
      // Step 1: Find what variable the load result is assigned to.
      // e.g. "$tags = $field->referencedEntities();" → $assignedVar = "tags".
      $assignedVar = $this->findAssignedVariable($loadCall, $nodeFinder, $method);
      if ($assignedVar === NULL) {
        // If the result isn't assigned to a variable we can track (e.g. it's
        // used inline or in a complex expression), we can't determine safety
        // so we conservatively say "not immutable-only".
        return FALSE;
      }

      // Step 2: Also track foreach loop variables derived from the collection.
      // e.g. "foreach ($tags as $tag)" → we also need to check calls on $tag.
      // Without this, "$tag->label()" would not be recognized as accessing the
      // loaded entities, causing a false negative.
      $trackedVars = [$assignedVar];
      $foreachNodes = $nodeFinder->findInstanceOf($method->stmts, Foreach_::class);
      foreach ($foreachNodes as $foreach) {
        if ($foreach->expr instanceof
        Variable            && is_string($foreach->expr->name)
            && $foreach->expr->name === $assignedVar
            && $foreach->valueVar instanceof
        Variable            && is_string($foreach->valueVar->name)
        ) {
          // This foreach iterates over our loaded collection, so its value
          // variable holds individual loaded entities.
          $trackedVars[] = $foreach->valueVar->name;
        }
      }

      // Step 3: Scan all method calls in the body. For any call on a tracked
      // variable, check if it's a non-immutable method.
      $allCalls = $nodeFinder->findInstanceOf($method->stmts, MethodCall::class);
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
   * Finds the variable name that a method call result is assigned to.
   *
   * Scans the method body for assignment expressions where the right-hand side
   * is the given method call, and returns the variable name from the left side.
   *
   * Example: "$paragraphs = $field->referencedEntities();" → "paragraphs".
   *
   * @return string|null
   *   The variable name, or NULL if the result isn't assigned to a simple
   *   variable (e.g. it's used inline or in a complex expression).
   */
  private function findAssignedVariable(MethodCall $loadCall, NodeFinder $nodeFinder, ClassMethod $method): ?string {
    $assigns = $nodeFinder->findInstanceOf($method->stmts, Assign::class);
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
   * Checks if a method call is made on any of the tracked variable names.
   *
   * This matches patterns like:
   *   $tags->method()       — direct call on the collection variable
   *   $tag->method()        — call on a foreach loop variable.
   *
   * This is a name-based heuristic. It doesn't do full data-flow analysis,
   * so a local variable with the same name but different origin would match.
   * In practice, PEVB build methods are short and this is reliable.
   *
   * @param \PhpParser\Node\Expr\MethodCall $call
   *   The method call to check.
   * @param string[] $varNames
   *   Variable names to match against (collection var + foreach loop vars).
   *
   * @return bool
   *   TRUE if the method call is on one of the tracked variables.
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
   * Checks if any CacheableMetadata methods are called in the method body.
   *
   * If the developer is already using addCacheableDependency, addCacheTags,
   * or createFromRenderArray anywhere in the method, we assume they are
   * handling cache metadata and don't flag the method.
   *
   * Note: this is permissive — a method could use CacheableMetadata for one
   * entity load but miss another. This is a deliberate trade-off to avoid
   * false positives. The skill-based audit catches these nuanced cases.
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
