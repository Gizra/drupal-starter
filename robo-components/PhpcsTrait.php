<?php

namespace RoboComponents;

use Robo\ResultData;

/**
 * Coding standard checks for Drupal.
 */
trait PhpcsTrait {

  /**
   * Perform a Code sniffer test, and fix when applicable.
   *
   * @return \Robo\ResultData|null
   *   If there was an error a result data object is returned. Or null if
   *   successful.
   */
  public function phpcs(): ?ResultData {
    $standards = [
      'Drupal',
      'DrupalPractice',
    ];

    $commands = [
      'phpcbf',
      'phpcs',
    ];

    $directories = [
      'modules/custom',
      'themes/custom',
      'profiles/custom',
      '../RoboFile.php',
      '../robo-components',
      'sites/default/settings.pantheon.php',
      'sites/bot_trap_protection.php',
      '../phpstan-rules',
      '../.bootstrap-fast.php',
    ];

    $error_code = NULL;

    foreach ($directories as $directory) {
      foreach ($standards as $standard) {
        $arguments = "--parallel=8 --standard=$standard -p --ignore=" . self::$themeName . "/dist,node_modules,server_default_content/content --colors --extensions=php,module,inc,install,test,profile,theme,css,yaml,yml,txt,md";

        foreach ($commands as $command) {
          $result = $this->_exec("cd web && ../vendor/bin/$command $directory $arguments");
          if (empty($error_code) && !$result->wasSuccessful()) {
            $error_code = $result->getExitCode();
          }
        }
      }
    }

    if (!empty($error_code)) {
      return new ResultData($error_code, 'PHPCS found some issues');
    }
    return NULL;
  }

}
