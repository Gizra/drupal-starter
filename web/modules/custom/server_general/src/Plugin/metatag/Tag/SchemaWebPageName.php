<?php

declare(strict_types=1);

namespace Drupal\server_general\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_name' meta tag.
 *
 * @MetatagTag(
 *   id = "schema_web_page_name",
 *   label = @Translation("name"),
 *   description = @Translation("The name/title of the web page."),
 *   name = "name",
 *   group = "schema_web_page",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaWebPageName extends SchemaNameBase {

}
