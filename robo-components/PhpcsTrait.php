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
    $standards = 'Drupal,DrupalPractice';

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
      '../scripts',
    ];

    $arguments = "--standard=$standards -p --ignore=" . self::$themeName . "/dist,node_modules,.parcel-cache --colors --extensions=php,module,inc,install,test,profile,theme,css,yaml,txt,md";

    // Step 1: Auto-fix what can be fixed (only if not in CI).
    // In CI, phpcbf can't commit changes, so we skip it for better performance.
    $is_ci = !empty(getenv('CI'));
    if (!$is_ci) {
      $this->say('Running phpcbf to auto-fix coding standard violations...');
      $command_list = [];
      foreach ($directories as $directory) {
        // Phpcbf exits with non-zero even on success when it fixes files.
        // We don't fail on phpcbf errors since phpcs will catch real issues.
        $command_list[] = "cd web && ../vendor/bin/phpcbf $directory $arguments || true";
      }

      $commands_file = tempnam(sys_get_temp_dir(), 'phpcbf_commands');
      file_put_contents($commands_file, implode("\n", $command_list));

      $this->_exec("parallel -j+0 < $commands_file");
      unlink($commands_file);
    }

    // Step 2: Check for remaining violations.
    $this->say('Running phpcs to check for coding standard violations...');
    $command_list = [];
    foreach ($directories as $directory) {
      $command_list[] = "cd web && ../vendor/bin/phpcs $directory $arguments";
    }

    $commands_file = tempnam(sys_get_temp_dir(), 'phpcs_commands');
    file_put_contents($commands_file, implode("\n", $command_list));

    $result = $this->_exec("parallel -j+0 --halt now,fail=1 < $commands_file");
    unlink($commands_file);

    if (!$result->wasSuccessful()) {
      $this->say('');
      $this->yell('PHPCS found coding standard violations!', 40, 'red');
      $this->say('Please review the errors above and fix them.');
      return new ResultData($result->getExitCode(), 'PHPCS found coding standard violations');
    }

    $this->say('');
    $this->say('✓ No coding standard violations found!');
    return NULL;
  }

}
