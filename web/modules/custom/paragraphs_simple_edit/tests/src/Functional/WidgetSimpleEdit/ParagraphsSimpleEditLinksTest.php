<?php

namespace Drupal\Tests\paragraphs_simple_edit\Functional\WidgetSimpleEdit;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Tests paragraphs simple edit links.
 */
class ParagraphsSimpleEditLinksTest extends ParagraphsSimpleEditTestBase {

  /**
   * Tests the dropdown links.
   */
  public function testDropDownLinks() {
    $this->loginAsAdmin();
    // Add two Paragraph types.
    $this->addParagraphsType('btext');
    $this->addParagraphsType('dtext');

    $content_type = 'paragraphed_test';
    $paragraph_field_name = 'paragraphs';

    $this->addParagraphedContentType($content_type, $paragraph_field_name);

    // Enter to the field config since the weight is set through the form.
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraph_field_name);
    $this->submitForm([], 'Save settings');

    $settings = ['edit_mode' => 'closed'];
    $this->setSimpleEditWidget('node', $content_type, $paragraph_field_name, $settings);

    $node = $this->createNode(['type' => $content_type]);

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

    // Create paragraphs & add to the node.
    foreach (['atext', 'btext'] as $type) {
      $this->addParagraphToEntity($type, $node, $paragraph_field_name);
    }
    $this->assertActionLinks(2, $node);

    $this->addParagraphToEntity('dtext', $node, $paragraph_field_name);
    $this->assertActionLinks(3, $node);
  }

  /**
   * Tests the widget empty text for node.
   */
  public function testWidgetEmptyTextForNode() {
    $this->loginAsAdmin();
    // Add two Paragraph types.
    $this->addParagraphsType('btext');
    $this->addParagraphsType('dtext');

    $content_type = 'paragraphed_test';
    $paragraph_field_name = 'paragraphs';

    $this->addParagraphedContentType($content_type, $paragraph_field_name);

    // Enter to the field config since the weight is set through the form.
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraph_field_name);
    $this->submitForm([], 'Save settings');

    $settings = ['edit_mode' => 'closed'];
    $this->setSimpleEditWidget('node', $content_type, $paragraph_field_name, $settings);

    // Go to node add page.
    $this->drupalGet('node/add/' . $content_type);

    $this->assertSession()->pageTextContains('Save the ' . $content_type . ' first to add new Paragraphs.');

    // Change paragraphs title text.
    $settings = [
      'title' => 'Item',
      'title_plural' => 'Items',
    ];
    $this->setParagraphsWidgetSettings($content_type, $paragraph_field_name, $settings);

    // Go to node add page.
    $this->drupalGet('node/add/' . $content_type);

    $this->assertSession()->pageTextContains('Save the ' . $content_type . ' first to add new Items.');
  }

  /**
   * Tests the widget empty text for user.
   */
  public function testWidgetEmptyTextForUser() {
    $this->loginAsAdmin();
    // Add two Paragraph types.
    $this->addParagraphsType('btext');
    $this->addParagraphsType('dtext');

    $paragraph_field_name = 'paragraphs';

    // Add paragraphs field to user.
    $this->addParagraphsField('user', $paragraph_field_name, 'user');

    // Enter to the field config since the weight is set through the form.
    $this->drupalGet('admin/config/people/accounts/fields/user.user.' . $paragraph_field_name);
    $this->submitForm([], 'Save settings');

    $settings = ['edit_mode' => 'closed'];
    $this->setSimpleEditWidget('user', 'user', $paragraph_field_name, $settings);

    // Go to user add page.
    $this->drupalGet('admin/people/create');

    $this->assertSession()->pageTextContains('Save the User first to add new Paragraphs.');

    // Change paragraphs title text.
    $settings = [
      'title' => 'Item',
      'title_plural' => 'Items',
    ];
    $this->setParagraphsWidgetSettings('user', $paragraph_field_name, $settings, NULL, 'user');

    // Go to user add page.
    $this->drupalGet('admin/people/create');

    $this->assertSession()->pageTextContains('Save the User first to add new Items.');
  }

  /**
   * Asserts order and quantity of add links.
   */
  protected function assertAddLinks(array $expected_links, ContentEntityInterface $node) {
    $this->drupalGet('node/' . $node->id() . '/edit');
    $links = $this->xpath('//a[@class="paragraphs-simple-edit-add-link"]');
    // Check if the buttons are in the same order as the given array.
    foreach ($links as $key => $link) {
      $this->assertEquals($link->getText(), $expected_links[$key]);
    }
    $this->assertEquals(count($links), count($expected_links), 'The amount of drop down links matches with the given array');
  }

  /**
   * Asserts quantity of edit/delete links.
   */
  protected function assertActionLinks(int $count, ContentEntityInterface $node) {
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit_links = $this->xpath('//a[@class="paragraphs-simple-edit-edit-link"]');
    $this->assertEquals(count($edit_links), $count, 'The amount of edit links matches with the given count');
    $delete_links = $this->xpath('//a[@class="paragraphs-simple-edit-delete-link"]');
    $this->assertEquals(count($delete_links), $count, 'The amount of delete links matches with the given count');
  }

}
