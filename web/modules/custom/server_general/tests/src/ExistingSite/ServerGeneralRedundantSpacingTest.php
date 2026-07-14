<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that class attributes on the /style-guide have no redundant spaces.
 *
 * Checks that class attributes on the /style-guide page have no redundant
 * leading, trailing, or repeated spaces. Uses the DOM crawler
 * (proper HTML parsing) to extract class attribute values, rather than regex
 * over raw markup, so that quoting style, comments, and non-attribute text
 * don't produce false positives/negatives.
 *
 * @group server_general
 */
class ServerGeneralRedundantSpacingTest extends ServerGeneralTestBase {

  /**
   * Tests that no class attribute contains redundant whitespace.
   */
  public function testNoRedundantSpacesInClassAttributes(): void {
    // Log in as admin to get access to the style-guide page.
    $user = $this->createUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);
    $this->drupalGet('/style-guide');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $html = (string) $this->getSession()->getPage()->getContent();

    $crawler = new Crawler($html);
    $classValues = $crawler->filter('[class]')->extract(['class']);

    $this->assertNotEmpty(
      $classValues,
      'No elements with a class attribute were found on /style-guide — check that the page rendered correctly.'
    );

    $offenders = [];
    foreach ($classValues as $index => $classValue) {
      // Leading space, trailing space, or 2+ consecutive spaces.
      if (preg_match('/^\s|\s{2,}|\s$/', $classValue) === 1) {
        $offenders[$index] = $classValue;
      }
    }

    $this->assertEmpty(
      $offenders,
      sprintf(
        "Found %d class attribute(s) with redundant spaces on /style-guide:\n%s",
        count($offenders),
        implode("\n", array_map(
          static fn (string $v): string => '"' . $v . '"',
          $offenders
        ))
      )
    );
  }

}
