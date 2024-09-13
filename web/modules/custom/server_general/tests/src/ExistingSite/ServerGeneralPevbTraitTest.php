<?php

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\server_general\ElementWrapTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test PEVB traits.
 */
class ServerGeneralPevbTraitTest extends ExistingSiteBase {

  use ElementWrapTrait;

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
