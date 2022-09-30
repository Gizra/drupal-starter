<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'hero_image' paragraph type.
 */
class ServerGeneralParagraphTypeHeroImageTest extends ServerGeneralParagraphTypeTestBase {

  const ENTITY_BUNDLE = 'hero_image';
  const REQUIRED_FIELDS = [
    'field_title',
    'field_image',
  ];
  const OPTIONAL_FIELDS = [
    'field_link',
    'field_subtitle',
  ];

}
