<?php

declare(strict_types=1);

namespace RoboComponents;

use Robo\Exception\TaskException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Cache enable/disable commands for local development.
 */
trait DevelopmentTrait {

  /**
   * Defines cache bins in settings.local.php.
   */
  const CACHE_BINS = [
    'Internal Page Cache' => '$settings[\'cache\'][\'bins\'][\'page\'] = \'cache.backend.null\';',
    'Render Cache' => '$settings[\'cache\'][\'bins\'][\'render\'] = \'cache.backend.null\';',
    'Dynamic Page Cache' => '$settings[\'cache\'][\'bins\'][\'dynamic_page_cache\'] = \'cache.backend.null\';',
    'Migrations Cache' => '$settings[\'cache\'][\'bins\'][\'discovery_migration\'] = \'cache.backend.memory\';',
  ];

  /**
   * Enable Drupal cache.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function cacheEnable(): void {
    $this->say("Enabling Drupal caching...");
    $this->setTwigCache(TRUE);

    $settings_file = $this->ensureSettingsLocalFileExists();
    $settings_content = file_get_contents($settings_file);

    foreach (self::CACHE_BINS as $label => $cache_bin) {
      if (strpos($settings_content, "# $cache_bin") !== FALSE) {
        $this->say('Skipping: ' . $label . ' (already enabled)');
        continue;
      }
      if (strpos($settings_content, $cache_bin) !== FALSE) {
        $this->say('Enabling: ' . $label);
        $this->taskReplaceInFile($settings_file)
          ->from($cache_bin)
          ->to("# $cache_bin")
          ->run();
      }
    }

    $this->taskExec('drush cr')->run();
    $this->verifyCacheState(TRUE);
    $this->say("Drupal caching enabled.");
  }

  /**
   * Disable Drupal cache.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function cacheDisable(): void {
    $this->say("Disabling Drupal caching...");
    $this->setTwigCache(FALSE);

    $settings_file = $this->ensureSettingsLocalFileExists();
    $settings_content = file_get_contents($settings_file);

    foreach (self::CACHE_BINS as $label => $cache_bin) {
      if (strpos($settings_content, "# $cache_bin") !== FALSE) {
        $this->say('Disabling: ' . $label);
        $this->taskReplaceInFile($settings_file)
          ->from("# $cache_bin")
          ->to($cache_bin)
          ->run();
        continue;
      }
      if (strpos($settings_content, $cache_bin) !== FALSE) {
        $this->say('Skipping: ' . $label . ' (already disabled)');
      }
    }

    $this->taskExec('drush cr')->run();
    $this->verifyCacheState(FALSE);
    $this->say("Drupal caching disabled.");
  }

  /**
   * Toggle Twig cache in development.services.yml.
   */
  protected function setTwigCache(bool $enabled): void {
    $servicesFile = 'web/sites/development.services.yml';

    if (!file_exists($servicesFile)) {
      $this->say("File 'development.services.yml' not found.");
      return;
    }

    $label = $enabled ? 'enabled' : 'disabled';

    try {
      $yamlData = Yaml::parseFile($servicesFile);
      $current = $yamlData['parameters']['twig.config']['cache'] ?? NULL;

      if ($current === !$enabled) {
        $yamlData['parameters']['twig.config']['cache'] = $enabled;
        file_put_contents($servicesFile, Yaml::dump($yamlData, 4, 2));
        $this->say("Twig caching $label.");
        return;
      }

      $this->say("Skipping: Twig cache (already $label).");
    }
    catch (ParseException $e) {
      $this->say('Unable to parse the YAML string: ' . $e->getMessage());
    }
  }

  /**
   * Helper to get settings.local.php file.
   *
   * @return string
   *   'settings.local.php' path.
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function ensureSettingsLocalFileExists(): string {
    $settings_file = 'web/sites/settings.local.php';
    $example_settings_file = 'web/sites/example.settings.local.php';

    if (!file_exists($settings_file)) {
      if (file_exists($example_settings_file)) {
        copy($example_settings_file, $settings_file);
        $this->say("Copied 'example.settings.local.php' to 'settings.local.php'");
      }
      else {
        $this->say("File 'example.settings.local.php' does not exist.");
        throw new TaskException($this, "Command failed: 'settings.local.php' or 'example.settings.local.php' do not exist.");
      }
    }

    $default_settings_file = 'web/sites/default/settings.php';

    if (!file_exists($default_settings_file)) {
      $this->say("settings.php not found.");
      throw new TaskException($this, "Command failed: 'settings.php' does not exist.");
    }

    $settings_content = file_get_contents($default_settings_file);

    $local_settings_enable = <<<PHP
if (file_exists(\$app_root . '/sites/settings.local.php')) {
  include \$app_root . '/sites/settings.local.php';
}
PHP;

    if (strpos($settings_content, $local_settings_enable) === FALSE) {
      $this->say("Including 'settings.local.php' file.");
      $this->taskWriteToFile($default_settings_file)
        ->append(TRUE)
        ->text($local_settings_enable)
        ->run();
      $this->say("Local settings enabled.");
    }
    else {
      $this->say("Skipping: Including 'settings.local.php' file (already included).");
    }

    return $settings_file;
  }

  /**
   * Verify cache state matches expectation via Drush.
   *
   * @param bool $should_be_enabled
   *   TRUE if cache should be active, FALSE if null backend.
   */
  protected function verifyCacheState(bool $should_be_enabled): void {
    $result = $this->taskExec('drush ev "echo \Drupal::service(\'cache.render\')->getBackendClass();"')
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      $this->yell("Could not verify cache state — drush eval failed.");
      return;
    }

    $backend = trim($result->getMessage());
    $is_null = str_contains($backend, 'NullBackend');

    if ($should_be_enabled && $is_null) {
      $this->yell("Verification failed: render cache still uses NullBackend.");
      return;
    }

    if (!$should_be_enabled && !$is_null) {
      $this->yell("Verification failed: render cache uses '$backend' instead of NullBackend.");
      return;
    }

    $state = $should_be_enabled ? 'enabled' : 'disabled';
    $this->say("Verification passed: cache is $state (backend: $backend).");
  }

}
