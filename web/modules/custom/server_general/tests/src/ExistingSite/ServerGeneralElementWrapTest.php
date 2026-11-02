<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;

/**
 * ElementWrapTrait tests.
 *
 * Could have been a functional test, but we already have everything setup, so
 * easier to write it as `ExistingSite` test.
 */
class ServerGeneralElementWrapTest extends ServerGeneralTestBase {

  use ElementWrapThemeTrait;

  /**
   * Tests ElementWrapTrait::filterEmptyElements.
   */
  public function testFilterEmptyElements() {
    // Empty element.
    $element = [];
    $expected = [];
    $result = $this->filterEmptyElements($element);
    $this->assertEquals($expected, $result);

    // Non-nested array.
    $element = [
      '#foo' => FALSE,
      0 => FALSE,
      '' => FALSE,
    ];
    $expected = [
      '#foo' => FALSE,
      0 => FALSE,
      '' => FALSE,
    ];
    $result = $this->filterEmptyElements($element);
    $this->assertEquals($expected, $result);

    // Mix of non-nested and nested array. As it has top level `#` we shouldn't
    // filter it at all.
    $element = [
      '#foo' => FALSE,
      0 => [],
      '' => [],
    ];
    $expected = [
      '#foo' => FALSE,
      0 => [],
      '' => [],
    ];
    $result = $this->filterEmptyElements($element);
    $this->assertEquals($expected, $result);

    // Nested array with all empty elements.
    $element = [
      0 => [],
      1 => [],
      '' => [],
    ];
    $expected = [];
    $result = $this->filterEmptyElements($element);
    $this->assertEquals($expected, $result);

    // Nested array with some existing elements.
    $element = [
      0 => [],
      1 => ['#foo' => FALSE],
      '' => [],
    ];
    $expected = [
      1 => ['#foo' => FALSE],
    ];
    $result = $this->filterEmptyElements($element);
    $this->assertEquals($expected, $result);
  }

}
