<?php

namespace Drupal\PHPStan\Custom;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

/**
 * Rule to check cache dependency objects implements the right interface.
 *
 * Calling ::addCacheableDependency($object) when $object does not implement
 * CacheableDependencyInterface effectively disables caching and should be
 * avoided.
 *
 * @see Drupal\Core\Cache\CacheableMetadata::createFromObject
 */
class CacheableDependency implements Rule {

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return MethodCall::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    if (!$node instanceof MethodCall ||
        !$node->name instanceof Identifier ||
        $node->name->toString() !== 'addCacheableDependency'
    ) {
      return [];
    }

    $object = $scope->getType($node->args[0]->value);

    // We need to check if isInstanceOf method exists as phpstan returns
    // MixedType for unknown objects.
    if (method_exists($object, 'isInstanceOf') && $object->isInstanceOf('Drupal\Core\Cache\CacheableDependencyInterface')) {
      return [];
    }
    return [
      RuleErrorBuilder::message('Calling addCacheableDependency($object) when $object does not implement CacheableDependencyInterface effectively disables caching and should be avoided.')
        ->identifier('cacheable.dependency')
        ->build(),
    ];
  }

}
