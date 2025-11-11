<?php

namespace Drupal\Tests\paragraphs_simple_edit\Functional\WidgetSimpleEdit;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\paragraphs\Functional\WidgetStable\ParagraphsTestBase;

/**
 * Base class for tests.
 */
abstract class ParagraphsSimpleEditTestBase extends ParagraphsTestBase {


  /**
   * Sets the Paragraphs widget to simple edit.
   */
  protected function setSimpleEditWidget($content_type, $paragraphs_field, $settings = []) {
    $form_display = EntityFormDisplay::load('node.' . $content_type . '.default')
      ->setComponent($paragraphs_field, [
        'type' => 'paragraphs_simple_edit_default',
        'settings' => $settings,
      ]);
    $form_display->save();
  }

}
