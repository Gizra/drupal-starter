<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'related_content' paragraph type.
 */
class ServerGeneralParagraphTypeRelatedContentTest extends ServerGeneralParagraphTypeTestBase {

  const ENTITY_BUNDLE = 'related_content';
  const REQUIRED_FIELDS = [
    'field_title',
    'field_related_content',
  ];
  const OPTIONAL_FIELDS = [
    'field_link',
  ];

}
