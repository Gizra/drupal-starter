<?php

declare(strict_types=1);

namespace Drupal\server_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Masterminds\HTML5;

/**
 * Strip the specified list of tags.
 *
 * Example:
 *
 * @code
 * process:
 *   bar:
 *     plugin: strip_tags
 *     tags:
 *       - style
 *       - script
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "strip_tags"
 * )
 */
class StripTags extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value_is_array = is_array($value);
    $text = (string) ($value_is_array ? $value['value'] : $value);

    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    // Compatibility for older HTML5 versions (e.g. in Drupal core 8.9.x).
    $dom_text = '<html><body>' . $text . '</body></html>';
    try {
      $dom = $html5->parse($dom_text);
      $tags = $this->configuration['tags'];
      foreach ($tags as $tag) {
        $items = $dom->getElementsByTagName($tag);
        $count = $items->count();
        $iterator = 0;
        for ($i = 0; $i < $count; $i++) {
          /** @var \DOMElement $item */
          $item = $items->item($iterator);
          $parent_node = $item->parentNode;
          if (empty($parent_node)) {
            // Increment iterator as no removal happened.
            $iterator++;
            continue;
          }
          $parent_node->removeChild($item);
        }
      }
      $result = $html5->saveHTML($dom->documentElement->firstChild->childNodes);
    }
    catch (\TypeError $e) {
      // Unable to parse the text into HTML.
      return $value;
    }

    if ($value_is_array) {
      $value['value'] = $result;
    }
    else {
      $value = $result;
    }
    return $value;
  }

}
