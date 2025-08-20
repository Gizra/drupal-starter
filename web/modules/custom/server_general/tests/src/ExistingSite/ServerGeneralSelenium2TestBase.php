<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\server_general\TestConfiguration;
use Drupal\Tests\server_general\Traits\MemoryManagementTrait;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Base test class for writing ExistingSite tests with JS capability.
 *
 * All JS tests should extend this class instead of
 * ExistingSiteSelenium2DriverTestBase.
 */
class ServerGeneralSelenium2TestBase extends ExistingSiteSelenium2DriverTestBase {

  use MemoryManagementTrait;

  /**
   * Static counter for screenshot enumeration per class-method combination.
   *
   * @var array
   */
  private static array $screenshotCounters = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $session = $this->getSession();
    // Make takeScreenshot() more developer friendly, capture
    // as many details as possible.
    $session->resizeWindow(TestConfiguration::BROWSER_WIDTH, TestConfiguration::BROWSER_HEIGHT);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->performMemoryCleanup();
  }

  /**
   * Take a screenshot and save to file.
   *
   * The filename is auto-generated based on calling class and method with
   * enumeration.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function takeScreenshot(): void {
    // Generate filename and get screenshot.
    $screenshot_name = $this->generateScreenshotName();
    $screenshot = $this->getDriverInstance()->getScreenshot();

    if (getenv('CI') === 'true') {
      // In CI environment, use AI to analyze the screenshot.
      $this->analyzeScreenshotWithAi($screenshot, $screenshot_name);
      return;
    }

    // Local environment: save screenshot to file.
    $this->saveScreenshotToFile($screenshot, $screenshot_name);
  }

  /**
   * Take a screenshot and analyze it using AI.
   *
   * Forces AI analysis regardless of environment for testing purposes.
   *
   * @param string|null $question
   *   Optional specific question to ask about the screenshot.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function takeScreenshotWithAi(?string $question = NULL): void {
    // Generate filename and get screenshot.
    $screenshot_name = $this->generateScreenshotName();
    $screenshot = $this->getDriverInstance()->getScreenshot();

    // Always use AI analysis.
    $this->analyzeScreenshotWithAi($screenshot, $screenshot_name, $question);
  }

  /**
   * Generate screenshot name based on calling class and method.
   *
   * @return string
   *   Generated screenshot name with enumeration.
   */
  private function generateScreenshotName(): string {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $caller = $backtrace[2];

    $class = basename(str_replace('\\', '/', $caller['class']));
    $method = $caller['function'];
    $key = "{$class}-{$method}";

    // Increment counter for this class-method combination.
    if (!isset(self::$screenshotCounters[$key])) {
      self::$screenshotCounters[$key] = 0;
    }
    self::$screenshotCounters[$key]++;

    return "{$key}-" . self::$screenshotCounters[$key];
  }

  /**
   * Save screenshot data to file.
   *
   * @param string $screenshot
   *   Base64 encoded PNG screenshot data.
   * @param string $screenshot_name
   *   Name of the screenshot file.
   */
  private function saveScreenshotToFile(string $screenshot, string $screenshot_name): void {
    $working_dir = getcwd();
    // Prepare the directories.
    $dirs = [
      "{$working_dir}/sites/simpletest/",
      "{$working_dir}/sites/simpletest/screenshots/",
    ];
    foreach ($dirs as $dir) {
      if (file_exists($dir)) {
        continue;
      }
      mkdir($dir);
    }

    // Save the screenshot.
    $filename = $screenshot_name . '.png';
    $screenshot_path = $dirs[1] . $filename;
    file_put_contents($screenshot_path, $screenshot);
    echo "🖼️  Screenshot '{$screenshot_name}' captured at {$screenshot_path}.\n";
  }

  /**
   * Analyze screenshot using AI service and output description + ASCII art.
   *
   * @param string $screenshot_data
   *   Base64 encoded PNG screenshot data.
   * @param string $screenshot_name
   *   Name/context of the screenshot for reference.
   * @param string|null $question
   *   Optional specific question to ask about the screenshot.
   */
  private function analyzeScreenshotWithAi(string $screenshot_data, string $screenshot_name, ?string $question = NULL): void {
    // Generate file path for reference.
    $working_dir = getcwd();
    $screenshot_path = "{$working_dir}/sites/simpletest/screenshots/{$screenshot_name}.png";

    $openai_token = getenv('OPEN_AI_TOKEN');
    if (empty($openai_token)) {
      echo "🖼️  Screenshot '{$screenshot_name}' captured at {$screenshot_path} but no OpenAI token available for AI analysis.\n";
      return;
    }

    try {
      // Ensure the screenshot data is properly base64 encoded.
      $base64_data = base64_encode($screenshot_data);

      // Use Drupal's HTTP client service.
      $http_client = \Drupal::httpClient();

      // Prepare the API request.
      $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
          [
            'role' => 'user',
            'content' => [
              [
                'type' => 'text',
                'text' => $this->buildPromptText($question),
              ],
              [
                'type' => 'image_url',
                'image_url' => [
                  'url' => 'data:image/png;base64,' . $base64_data,
                ],
              ],
            ],
          ],
        ],
        'max_tokens' => 1000,
      ];

      $response = $http_client->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $openai_token,
        ],
        'json' => $data,
        'timeout' => 30,
      ]);

      $http_code = $response->getStatusCode();
      $response_body = $response->getBody()->getContents();

      if ($http_code === 200 && $response_body) {
        $result = Json::decode($response_body);
        if (isset($result['choices'][0]['message']['content'])) {
          echo "\n🤖 AI Analysis of Screenshot '{$screenshot_name}':\n";
          echo str_repeat('=', 60) . "\n";
          echo $result['choices'][0]['message']['content'] . "\n";
          echo str_repeat('=', 60) . "\n\n";
        }
        else {
          echo "🖼️  Screenshot '{$screenshot_name}' captured at {$screenshot_path} but AI analysis failed to parse response.\n";
        }
      }
      else {
        $message = "🖼️  Screenshot '{$screenshot_name}' captured at {$screenshot_path} but AI analysis failed (HTTP {$http_code}).";
        echo $message . "\n";
        echo "Response body: " . $response_body . "\n";
      }
    }
    catch (\Exception $e) {
      echo "🖼️  Screenshot '{$screenshot_name}' captured at {$screenshot_path} but AI analysis failed: {$e->getMessage()}\n";
    }
  }

  /**
   * Build the prompt text for AI analysis.
   *
   * @param string|null $question
   *   Optional specific question to ask about the screenshot.
   *
   * @return string
   *   The formatted prompt text.
   */
  private function buildPromptText(?string $question = NULL): string {
    $parts = [];

    if ($question) {
      $parts[] = "Please analyze this screenshot from a Selenium test and answer: {$question}";
    }
    else {
      $parts[] = 'Please analyze this screenshot from a Selenium test and provide:';
    }

    $parts[] = "Also provide:";
    $parts[] = '1. A detailed description of what you see on the page. If there is an error message, include it in the description.';
    $parts[] = '2. An ASCII art representation of the main visual elements on the page, including any error messages or important UI components. ASCII should be in landscape layout.';
    $parts[] = '';
    $parts[] = 'Format your response as:';

    if ($question) {
      $parts[] = 'QUESTION:';
      $parts[] = $question;
      $parts[] = '';
      $parts[] = 'ANSWER:';
      $parts[] = '[your answer here]';
      $parts[] = '';
    }

    $parts[] = 'DESCRIPTION:';
    $parts[] = '[your description here]';
    $parts[] = '';
    $parts[] = 'ASCII:';
    $parts[] = '[your ascii art here]';

    return implode("\n", $parts);
  }
}
