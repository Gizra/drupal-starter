<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\server_general\ThemeTrait\ElementWrapThemeTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test PEVB traits.
 */
class ServerGeneralPevbTraitTest extends ExistingSiteBase {

  use ElementWrapThemeTrait;

  /**
   * Test wrapConditionalContainerBottomPadding.
   */
  public function testWrapConditionalContainerBottomPadding() {
    $element = [];
    $list = new EntityReferenceFieldItemList(new DataDefinition());
    $element = $this->wrapConditionalContainerBottomPadding($element, $list);
    $this->assertEquals([], $element);
  }

}
