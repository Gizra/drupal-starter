<?php

declare(strict_types=1);

namespace Drupal\Tests\server_ai\Unit;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\server_ai\OpenAiClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the OpenAiClient.
 *
 * @coversDefaultClass \Drupal\server_ai\OpenAiClient
 *
 * @group server_ai
 */
final class OpenAiClientTest extends UnitTestCase {

  /**
   * Builds an OpenAiClient whose HTTP layer returns the given output text.
   *
   * Wraps the text in a realistic OpenAI Responses-API JSON body whose
   * `output[].content[]` carries an `output_text` part.
   *
   * @param string $outputText
   *   The model output text to embed in the response body.
   *
   * @return \Drupal\server_ai\OpenAiClient
   *   The configured client.
   */
  private function clientReturning(string $outputText): OpenAiClient {
    $body = json_encode([
      'output' => [
        [
          'type' => 'message',
          'content' => [
            ['type' => 'output_text', 'text' => $outputText],
          ],
        ],
      ],
    ]);
    $handler = HandlerStack::create(new MockHandler([new Response(200, [], $body)]));
    $http = new Client(['handler' => $handler]);

    return new OpenAiClient($http, $this->keyRepository(), $this->configPagesLoader());
  }

  /**
   * Tests that a flagged JSON verdict is parsed from the API response.
   *
   * @covers ::checkSensitivity
   */
  public function testCheckSensitivityParsesJsonVerdict(): void {
    $client = $this->clientReturning('{"flagged": true, "reason": "hate speech"}');
    $verdict = $client->checkSensitivity('some bad question', 'No hate speech.');
    $this->assertTrue($verdict->flagged);
    $this->assertSame('hate speech', $verdict->reason);
  }

  /**
   * Tests that an empty policy short-circuits without an HTTP call.
   *
   * The Guzzle handler queue is empty, so any HTTP attempt would throw,
   * proving that no request is made.
   *
   * @covers ::checkSensitivity
   */
  public function testEmptyPolicyIsNeverFlagged(): void {
    $handler = HandlerStack::create(new MockHandler([]));
    $http = new Client(['handler' => $handler]);
    $client = new OpenAiClient($http, $this->keyRepository(), $this->configPagesLoader());

    $verdict = $client->checkSensitivity('anything', '   ');
    $this->assertFalse($verdict->flagged);
    $this->assertSame('', $verdict->reason);
  }

  /**
   * Tests that generateTitle returns the trimmed output text.
   *
   * @covers ::generateTitle
   */
  public function testGenerateTitleReturnsTrimmedText(): void {
    $client = $this->clientReturning('  My Title  ');
    $this->assertSame('My Title', $client->generateTitle('What is the meaning of life?'));
  }

  /**
   * Creates a mock key repository returning a test token.
   *
   * @return \Drupal\key\KeyRepositoryInterface
   *   The mocked key repository.
   */
  private function keyRepository(): KeyRepositoryInterface {
    $key = $this->createMock(KeyInterface::class);
    $key->method('getKeyValue')->willReturn('test-token');

    $keyRepo = $this->createMock(KeyRepositoryInterface::class);
    $keyRepo->method('getKey')->with('server_ai_openai_token')->willReturn($key);

    return $keyRepo;
  }

  /**
   * Creates a mock config pages loader.
   *
   * Returns NULL for load(), so the configured model falls back to the
   * default 'gpt-4o'.
   *
   * @return \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   *   The mocked config pages loader.
   */
  private function configPagesLoader(): ConfigPagesLoaderServiceInterface {
    $loader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $loader->method('load')->willReturn(NULL);

    return $loader;
  }

}
