<?php

namespace Drupal\Tests\server_general\ExistingSite;

/**
 * Test 'cta' paragraph type.
 */
class ServerGeneralParagraphTypeCtaTest extends ServerGeneralParagraphTypeTestBase {

  const ENTITY_BUNDLE = 'cta';
  const REQUIRED_FIELDS = [
    'field_link',
    'field_subtitle',
    'field_title',
  ];

}
