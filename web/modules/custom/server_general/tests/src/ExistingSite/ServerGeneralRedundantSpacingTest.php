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
 * The check is scoped to an allow-list of root selectors that correspond to
 * markup owned by this project (custom theme + custom modules), so that it
 * doesn't flag markup provided by Drupal Core or contrib modules (e.g. the
 * admin toolbar, contextual links, or the messages region) that we can't fix
 * ourselves.
 *
 * @group server_general
 */
class ServerGeneralRedundantSpacingTest extends ServerGeneralTestBase {

  /**
   * Root selectors that scope the test to markup owned by this project.
   *
   * Each selector is checked both for its own class attribute and for the
   * class attribute of every descendant element.
   *
   * @var string[]
   */
  private const SCANNED_ROOT_SELECTORS = [
    // Style-guide component sandbox (everything rendered by
    // StyleGuideController).
    'dl.accordion',
    // Main navigation menu (menu--main.html.twig), covers both the
    // mobile <ul class="main-menu"> and desktop <div class="main-menu">
    // variants.
    '.main-menu',
  ];

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

    $classValues = [];
    foreach (self::SCANNED_ROOT_SELECTORS as $selector) {
      // The root element's own class attribute.
      $classValues = array_merge(
        $classValues,
        $crawler->filter($selector)->extract(['class'])
      );
      // The class attribute of every descendant element.
      $classValues = array_merge(
        $classValues,
        $crawler->filter($selector . ' [class]')->extract(['class'])
      );
    }

    $this->assertNotEmpty(
      $classValues,
      'No elements with a class attribute were found in the scanned regions (' . implode(', ', self::SCANNED_ROOT_SELECTORS) . ') on /style-guide — check that the page rendered correctly or that the selectors still match the markup.'
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
