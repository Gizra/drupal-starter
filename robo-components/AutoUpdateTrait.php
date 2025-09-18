<?php

namespace RoboComponents;

use Drupal\update\UpdateManagerInterface;

/**
 * Automatically update core and modules.
 */
trait AutoUpdateTrait {

  /**
   * Update drupal core and modules.
   *
   * @throws \Exception
   */
  public function updateModules() {
    if (!\Drupal::moduleHandler()->moduleExists('update')) {
      throw new \Exception("The update module should be installed in order to run this command.");
    }
    $this->say("Update module is installed, checking status of projects.");
    $this->say("In case of some errors manually loading /admin/reports/updates can help.");

    if (!($available = update_get_available(TRUE))) {
      $this->say("Cannot fetch info about the releases.");
    }
    \Drupal::moduleHandler()->loadInclude('update', 'compare.inc');
    $data = update_calculate_project_data($available);
    foreach ($data as $project) {
      if (!isset($project['recommended'])) {
        $this->yell('No recommended version is set for ' . $project['name']);
        continue;
      }
      // Drupal core is simply called drupal.
      $package = $project['name'] == 'drupal' ? 'core-*' : $project['name'];
      if ($project['status'] == UpdateManagerInterface::CURRENT && $project['existing_version'] === $project['recommended']) {
        $this->say($project['name'] . ' is up-to-date.');
        // No need to update.
        continue;
      }
      $version = $project['recommended'];
      // Example input: "8.x-2.19".
      if (preg_match('/^\d+\.x-(\d+)\.(\d+)$/', $project['recommended'], $matches)) {
        $major = $matches[1];
        $minor = $matches[2];
        $version = "{$major}.{$minor}";
      }

      $this->say('Updating ' . $package . ' to version ' . $version);
      $exit_code = $this->taskExec("composer update 'drupal/" . $package . ":^" . $version . "' -W")
        ->printOutput(TRUE)
        ->run()
        ->getExitCode();
      if ($exit_code !== 0) {
        throw new \Exception("There was an error updating " . $package . " to version " . $version);
      }

      $current_branch = trim(`git symbolic-ref --short HEAD`);

      // Don't commit to master/main branch.
      if (in_array($current_branch, ['master', 'main'], TRUE)) {
        throw new \Exception("This command cannot be run on the {$current_branch} branch.");
      }
      // Check if composer.lock has changes. Using the -W flag can
      // result in modules already being updated.
      $lock_changed = trim(`git status --porcelain composer.lock`);
      if (!$lock_changed) {
        $this->say($package . 'is already at version ' . $version);
        continue;
      }
      // Update successful, add composer.lock to staging area,
      // then commit it.
      $this->taskExec("git add composer.lock")->printOutput(TRUE)->run();
      $git_command = "git commit -m 'Update " . $package . ' to ' . $version . "'";
      $this->taskExec($git_command)->printOutput(TRUE)->run();
    }
  }

}
