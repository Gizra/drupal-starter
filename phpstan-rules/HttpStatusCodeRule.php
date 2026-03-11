<?php

namespace Drupal\PHPStan\Custom;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\LNumber;

/**
 * Custom PHPStan rule to enforce using Symfony Response constants.
 *
 * Detects HTTP status codes and suggests using Symfony Response constants.
 *
 * This rule checks for numeric HTTP status codes in statusCodeEquals() calls
 * and suggests using the corresponding Symfony Response class constants.
 */
class HttpStatusCodeRule implements Rule {

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
    if (!$node instanceof MethodCall) {
      return [];
    }

    // Check if the method name is statusCodeEquals.
    if (!$node->name instanceof Identifier || $node->name->toString() !== 'statusCodeEquals') {
      return [];
    }

    // Check if the first argument is a numeric literal.
    if (!isset($node->args[0]) || !$node->args[0]->value instanceof LNumber) {
      return [];
    }

    $statusCode = $node->args[0]->value->value;

    // Common HTTP status codes and their constant names.
    $statusCodes = [
      100 => 'HTTP_CONTINUE',
      101 => 'HTTP_SWITCHING_PROTOCOLS',
      102 => 'HTTP_PROCESSING',
      200 => 'HTTP_OK',
      201 => 'HTTP_CREATED',
      202 => 'HTTP_ACCEPTED',
      203 => 'HTTP_NON_AUTHORITATIVE_INFORMATION',
      204 => 'HTTP_NO_CONTENT',
      205 => 'HTTP_RESET_CONTENT',
      206 => 'HTTP_PARTIAL_CONTENT',
      300 => 'HTTP_MULTIPLE_CHOICES',
      301 => 'HTTP_MOVED_PERMANENTLY',
      302 => 'HTTP_FOUND',
      303 => 'HTTP_SEE_OTHER',
      304 => 'HTTP_NOT_MODIFIED',
      305 => 'HTTP_USE_PROXY',
      307 => 'HTTP_TEMPORARY_REDIRECT',
      308 => 'HTTP_PERMANENTLY_REDIRECT',
      400 => 'HTTP_BAD_REQUEST',
      401 => 'HTTP_UNAUTHORIZED',
      402 => 'HTTP_PAYMENT_REQUIRED',
      403 => 'HTTP_FORBIDDEN',
      404 => 'HTTP_NOT_FOUND',
      405 => 'HTTP_METHOD_NOT_ALLOWED',
      406 => 'HTTP_NOT_ACCEPTABLE',
      407 => 'HTTP_PROXY_AUTHENTICATION_REQUIRED',
      408 => 'HTTP_REQUEST_TIMEOUT',
      409 => 'HTTP_CONFLICT',
      410 => 'HTTP_GONE',
      411 => 'HTTP_LENGTH_REQUIRED',
      412 => 'HTTP_PRECONDITION_FAILED',
      413 => 'HTTP_REQUEST_ENTITY_TOO_LARGE',
      414 => 'HTTP_REQUEST_URI_TOO_LONG',
      415 => 'HTTP_UNSUPPORTED_MEDIA_TYPE',
      416 => 'HTTP_REQUESTED_RANGE_NOT_SATISFIABLE',
      417 => 'HTTP_EXPECTATION_FAILED',
      418 => 'HTTP_I_AM_A_TEAPOT',
      422 => 'HTTP_UNPROCESSABLE_ENTITY',
      423 => 'HTTP_LOCKED',
      424 => 'HTTP_FAILED_DEPENDENCY',
      425 => 'HTTP_TOO_EARLY',
      426 => 'HTTP_UPGRADE_REQUIRED',
      428 => 'HTTP_PRECONDITION_REQUIRED',
      429 => 'HTTP_TOO_MANY_REQUESTS',
      431 => 'HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE',
      451 => 'HTTP_UNAVAILABLE_FOR_LEGAL_REASONS',
      500 => 'HTTP_INTERNAL_SERVER_ERROR',
      501 => 'HTTP_NOT_IMPLEMENTED',
      502 => 'HTTP_BAD_GATEWAY',
      503 => 'HTTP_SERVICE_UNAVAILABLE',
      504 => 'HTTP_GATEWAY_TIMEOUT',
      505 => 'HTTP_VERSION_NOT_SUPPORTED',
      506 => 'HTTP_VARIANT_ALSO_NEGOTIATES',
      507 => 'HTTP_INSUFFICIENT_STORAGE',
      508 => 'HTTP_LOOP_DETECTED',
      510 => 'HTTP_NOT_EXTENDED',
      511 => 'HTTP_NETWORK_AUTHENTICATION_REQUIRED',
    ];

    if (isset($statusCodes[$statusCode])) {
      return [
        RuleErrorBuilder::message(sprintf(
          'Hardcoded HTTP status code %d should be replaced with Response::%s constant.',
          $statusCode,
          $statusCodes[$statusCode]
        ))->identifier('http.statuscode')
          ->build(),
      ];
    }

    return [];
  }

}
