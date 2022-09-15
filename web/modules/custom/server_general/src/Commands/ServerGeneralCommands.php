<?php

namespace Drupal\server_general\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\node\NodeInterface;
use Drush\Commands\DrushCommands;

/**
 * Server General Drush commands.
 */
class ServerGeneralCommands extends DrushCommands {

  /**
   * Command description here.
   *
   * @usage server_general:set-homepage
   *   Sets the homepage to the migrated "Homepage" landing page node.
   *
   * @command server_general:set-homepage
   * @aliases set-homepage
   */
  public function setHomepageAfterInstall() {
    /** @var NodeInterface[] $homepage */
    $homepage = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => 'Homepage',
      'type' => 'landing_page',
      'status' => NodeInterface::PUBLISHED,
    ]);
    if (empty($homepage)) {
      $this->logger()->error(dt('Unable to find any published landing_page nodes titled "Homepage".'));
      return;
    }

    $homepage = reset($homepage);
    $front = "/node/{$homepage->id()}";
    $config = \Drupal::configFactory()->getEditable('system.site');
    $config->set('page.front', $front);
    $config->save();

    $this->logger()->success(dt('Homepage set to @front.', [
      '@front' => $front,
    ]));
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command server_general:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}
