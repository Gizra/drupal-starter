<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\HttpFoundation\Response;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the AI-crawler baseline shipped in robots.txt.
 */
class ServerGeneralRobotsTxtTest extends ExistingSiteBase {

  /**
   * Tests that robots.txt declares an explicit, per-vendor AI-bot policy.
   */
  public function testRobotsTxtAiBots() {
    $this->drupalGet('/robots.txt');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $content = $this->getSession()->getPage()->getContent();

    // Each AI bot family must be named explicitly, so the policy can be tuned
    // per vendor and per bot rather than relying on a blanket "AI bot" rule.
    $expected_user_agents = [
      // OpenAI.
      'GPTBot',
      'OAI-SearchBot',
      'ChatGPT-User',
      // Anthropic.
      'ClaudeBot',
      'Claude-SearchBot',
      'Claude-User',
      // Perplexity.
      'PerplexityBot',
      'Perplexity-User',
      // Meta.
      'Meta-ExternalAgent',
      // Google training opt-out.
      'Google-Extended',
    ];
    foreach ($expected_user_agents as $user_agent) {
      $this->assertStringContainsString("User-agent: $user_agent", $content, "robots.txt names the $user_agent bot.");
    }

    // Google-Extended is the one opt-out: it must be disallowed from the whole
    // site to opt out of Gemini/Vertex training.
    $google_extended = strstr($content, 'User-agent: Google-Extended');
    $this->assertStringContainsString('Disallow: /', $google_extended, 'Google-Extended is disallowed from the entire site.');
  }

}
