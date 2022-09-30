<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'text' paragraph type.
 */
class ServerGeneralParagraphTypeTextTest extends ServerGeneralParagraphTypeTestBase {

  const ENTITY_BUNDLE = 'text';
  const REQUIRED_FIELDS = [
    'field_body',
  ];
  const OPTIONAL_FIELDS = [
    'field_title',
  ];

}
