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
    if (!($available = update_get_available(TRUE))) {
      $this->say("Cannot fetch info about the releases.");
    }
    \Drupal::moduleHandler()->loadInclude('update', 'compare.inc');
    // @phpstan-ignore-next-line
    $data = update_calculate_project_data($available);
    foreach ($data as $project) {
      if (isset($project['recommended'])) {
        if ($project['status'] != UpdateManagerInterface::CURRENT || $project['existing_version'] !== $project['recommended']) {
          $this->say('Updating the ' . $project['name'] . ' module to version ' . $project['recommended'] . '.');
          $package = $project['name'] == 'drupal' ? 'core-*' : $project['name'];
          $exit_code = $this->taskExec("composer update 'drupal/" . $package . ":^" . $project['recommended'] . "' -W")
            ->printOutput(TRUE)
            ->run()
            ->getExitCode();
          if ($exit_code === 0) {
            // Update successful, add composer.lock to staging area,
            // then commit it.
            $this->taskExec("git add composer.lock")->printOutput(TRUE)->run();
            $git_command = "git commit -m 'Update " . $package . ' to ' . $project['recommended'] . "'";
            $this->taskExec($git_command)->printOutput(TRUE)->run();
          }
          else {
            $this->yell('There was an error updating ' . $package . ' to version ' . $project['recommended']);
          }
        }
      }
    }
  }

}
