<?php

namespace RoboComponents\TranslationManagement;

use Robo\ResultData;
use RoboComponents\DeploymentTrait;

/**
 * Logic to import translations into Drupal UI translations.
 *
 * Imports translations from "config/po_files/[langcode].po" files.
 */
trait ImportToUi {

  use DeploymentTrait;

  /**
   * Import the interface translations from a PO file.
   *
   * @param bool $is_local
   *   Whether to run Drush locally (TRUE), or on Pantheon (FALSE).
   *   Default: TRUE.
   * @param string $env
   *   The Pantheon environment to run on, if $is_local = FALSE. Default: test.
   *
   * @return \Robo\ResultData
   *   The result.
   *
   * @throws \Exception
   */
  public function localeImport(bool $is_local = TRUE, string $env = 'test'): ResultData {
    $commands = [];
    if ($is_local) {
      foreach (self::INSTALLED_LANGUAGES as $language) {
        $commands[] = "drush locale:import --override=not-customized $language ../config/po_files/$language.po";
      }
    }
    else {
      $pantheon_info = $this->getPantheonNameAndEnv();
      $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;
      foreach (self::INSTALLED_LANGUAGES as $language) {
        $commands[] = "terminus drush $pantheon_terminus_environment -- locale:import --override=not-customized $language ../config/po_files/$language.po";
      }
    }
    return $this->_exec(implode(' && ', $commands));
  }

}
