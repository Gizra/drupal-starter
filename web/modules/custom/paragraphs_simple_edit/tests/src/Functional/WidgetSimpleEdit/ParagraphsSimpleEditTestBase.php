<?php

namespace Drupal\Tests\paragraphs_simple_edit\Functional\WidgetSimpleEdit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\Functional\WidgetStable\ParagraphsTestBase;

/**
 * Base class for tests.
 */
abstract class ParagraphsSimpleEditTestBase extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
    'paragraphs_simple_edit',
  ];

  /**
   * Create & add paragraph to the entity.
   */
  protected function addParagraphToEntity(string $type, ContentEntityInterface $entity, string $paragraph_field_name) {
    $paragraph = Paragraph::create([
      'type' => $type,
    ]);
    $paragraph->setParentEntity($entity, $paragraph_field_name);
    $paragraph->save();
    // Save the entity as well.
    $entity->{$paragraph_field_name}->appendItem($paragraph);
    $entity->save();
  }

  /**
   * Sets the Paragraphs widget to simple edit.
   */
  protected function setSimpleEditWidget(string $content_type, string $paragraph_field_name, array $settings = []) {
    $form_display = EntityFormDisplay::load('node.' . $content_type . '.default')
      ->setComponent($paragraph_field_name, [
        'type' => 'paragraphs_simple_edit_default',
        'settings' => $settings,
      ]);
    $form_display->save();
  }

}
