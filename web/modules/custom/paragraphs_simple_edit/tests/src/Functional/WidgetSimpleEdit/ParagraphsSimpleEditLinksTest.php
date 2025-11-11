<?php

namespace Drupal\Tests\paragraphs_simple_edit\Functional\WidgetSimpleEdit;

use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests paragraphs simple edit links.
 */
class ParagraphsSimpleEditLinksTest extends ParagraphsSimpleEditTestBase {

  /**
   * Tests the add dropdown links.
   */
  public function testDropDownAddLinks() {
    $this->loginAsAdmin();
    // Add two Paragraph types.
    $this->addParagraphsType('btext');
    $this->addParagraphsType('dtext');

    $content_type = 'paragraphed_test';
    $paragraph_field_name = 'paragraphs';

    $this->addParagraphedContentType($content_type, $paragraph_field_name);
    $node = $this->createNode(['type' => $content_type]);
    // Enter to the field config since the weight is set through the form.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/fields/node.' . $content_type . '.' . $paragraph_field_name);
    $this->submitForm([], 'Save settings');

    $settings = [
      'edit_mode' => 'closed',
    ];
    $this->setSimpleEditWidget($content_type, $paragraph_field_name, $settings);

    $this->assertAddLinks(['Add btext', 'Add dtext'], $node);

    $this->addParagraphsType('atext');
    $this->assertAddLinks(['Add btext', 'Add dtext', 'Add atext'], $node);

    $this->setParagraphsTypeWeight($content_type, 'dtext', 2, $paragraph_field_name);
    $this->assertAddLinks(['Add dtext', 'Add btext', 'Add atext'], $node);

    $this->setAllowedParagraphsTypes($content_type, ['dtext', 'atext'], TRUE, $paragraph_field_name);
    $this->assertAddLinks(['Add dtext', 'Add atext'], $node);

    $this->setParagraphsTypeWeight($content_type, 'atext', 1, $paragraph_field_name);
    $this->assertAddLinks(['Add atext', 'Add dtext'], $node);

    $this->setAllowedParagraphsTypes($content_type, ['atext', 'dtext', 'btext'], TRUE, $paragraph_field_name);
    $this->assertAddLinks(['Add atext', 'Add dtext', 'Add btext'], $node);
  }

  /**
   * Asserts order and quantity of add links.
   */
  protected function assertAddLinks($options, $node) {
    $this->drupalGet('node/' . $node->id() . '/edit');
    $links = $this->xpath('//a[@class="paragraphs-simple-edit-add-link"]');
    // Check if the buttons are in the same order as the given array.
    foreach ($links as $key => $link) {
      $this->assertEquals($link->getValue(), $options[$key]);
    }
    $this->assertEquals(count($links), count($options), 'The amount of drop down links matches with the given array');
  }

}
