<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'news' content type.
 */
class ServerGeneralContentTypeNews extends ServerGeneralContentTypeTestBase {

  const ENTITY_BUNDLE = 'news';
  const REQUIRED_FIELDS = [
    'field_body',
  ];
  const OPTIONAL_FIELDS = [
    'field_featured_image',
    'field_tags',
  ];

}
