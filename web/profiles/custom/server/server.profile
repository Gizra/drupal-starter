<?php

/**
 * @file
 * Server install profile.
 */

/**
 * Alters install tasks, preventing downloading translations from drupal.org.
 *
 * @param array $tasks
 *   An array of information about the task to be run.
 * @param array $install_state
 *   An array of information about the current installation state.
 */
function server_install_tasks_alter(array &$tasks, array $install_state) {
  unset($tasks['install_config_download_translations']);
  unset($tasks['install_import_translations']);
  unset($tasks['install_finish_translations']);
}
