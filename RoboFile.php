<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Robo\Exception\TaskException;
use Robo\Tasks;
use RoboComponents\BootstrapTrait;
use RoboComponents\DeploymentTrait;
use RoboComponents\PhpcsTrait;
use RoboComponents\ReleaseNotesTrait;
use RoboComponents\SecurityTrait;
use RoboComponents\ThemeTrait;
use RoboComponents\TranslationManagement\ExportFromConfig;
use RoboComponents\TranslationManagement\ImportToConfig;
use RoboComponents\TranslationManagement\ImportToUi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

$GLOBALS['drupal_autoloader'] = require_once 'web/autoload.php';

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use BootstrapTrait;
  use DeploymentTrait;
  use ExportFromConfig;
  use ImportToConfig;
  use ImportToUi;
  use PhpcsTrait;
  use ReleaseNotesTrait;
  use ThemeTrait;
  use SecurityTrait;

  /**
   * Defines a list of languages installed on the site.
   *
   * Edit as languages are installed/removed. This is used for translation
   * management.
   *
   * Do not include 'en', 'und' or 'zxx'.
   *
   * @see \TranslationManagement\ExportFromConfig
   * @see \TranslationManagement\ImportToConfig
   */
  const INSTALLED_LANGUAGES = [
    'ar',
    'es',
  ];

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
   * Bootstraps Drupal 8 in addition to Robo.
   *
   * @throws \Exception
   */
  public function __construct() {
    if (!file_exists('web/sites/default/settings.php')) {
      // Drupal is not yet initialized, do not attempt to boot.
      return;
    }
    try {
      chdir(__DIR__ . '/web');
      $request = Request::createFromGlobals();
      $kernel = DrupalKernel::createFromRequest($request, $GLOBALS['drupal_autoloader'], 'prod');
      $kernel->handle($request);
      chdir(__DIR__);
    }
    catch (\Exception $e) {
      // Do not fail, there are several commands that do not need Drupal.
      $this->yell($e->getMessage());
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
    // Step 1: Make sure settings.local.php exists.
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

    // Step 2: Make sure 'settings.local.php' is included in 'settings.php`.
    $default_settings_file = 'web/sites/default/settings.php';

    if (!file_exists($default_settings_file)) {
      $this->say("settings.php not found.");
      throw new TaskException($this, "Command failed: 'settings.php' does not exist.");
    }

    // Read the settings file content.
    $settings_content = file_get_contents($default_settings_file);

    // Set block of code that includes 'settings.local.php'.
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
   * Enable Drupal cache.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function cacheEnable() {
    $this->say("Enabling Drupal caching...");

    $this->say("Enabling Twig caching...");

    $servicesFile = 'web/sites/development.services.yml';

    if (!file_exists($servicesFile)) {
      $this->say("File 'development.services.yml' not found.");
      return;
    }

    try {
      // Parse the YAML file into an array.
      $yamlData = Yaml::parseFile($servicesFile);

      // Modifying the twig.config values.
      if (isset($yamlData['parameters']['twig.config']['cache']) && $yamlData['parameters']['twig.config']['cache'] == FALSE) {
        $yamlData['parameters']['twig.config']['cache'] = TRUE;
        // Dump the modified array back into the YAML file.
        // 4 for indentation, 2 for spaces.
        file_put_contents($servicesFile, Yaml::dump($yamlData, 4, 2));
        $this->say("Twig caching enabled.");
      }
      else {
        $this->say("Skipping: Twig cache (already enabled).");
      }
    }
    catch (ParseException $e) {
      $this->say('Unable to parse the YAML string: ' . $e->getMessage());
    }

    // Ensure settings.local.php exists.
    $settings_file = $this->ensureSettingsLocalFileExists();
    // Read the settings.local.php file content.
    $settings_content = file_get_contents($settings_file);

    foreach (self::CACHE_BINS as $label => $cache_bin) {
      // Check if the line is already commented out with a single #.
      if (strpos($settings_content, "# $cache_bin") !== FALSE) {
        $this->say('Skipping: ' . $label . ' (already enabled)');
      }
      elseif (strpos($settings_content, $cache_bin) !== FALSE) {
        $this->say('Enabling: ' . $label);
        // Comment out the settings for the cache bin.
        $this->taskReplaceInFile($settings_file)
          ->from($cache_bin)
          ->to("# $cache_bin")
          ->run();
      }
    }
    // Clear Drupal cache.
    $this->taskExec('drush cr')->run();

    $this->say("Drupal caching enabled.");
  }

  /**
   * Disable Drupal cache.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function cacheDisable() {
    $this->say("Disabling Drupal caching...");

    $this->say("Disabling Twig caching...");

    $servicesFile = 'web/sites/development.services.yml';

    if (!file_exists($servicesFile)) {
      $this->say("File 'development.services.yml' not found.");
      return;
    }

    try {
      // Parse the YAML file into an array.
      $yamlData = Yaml::parseFile($servicesFile);

      // Modifying the twig.config values.
      if (isset($yamlData['parameters']['twig.config']['cache']) && $yamlData['parameters']['twig.config']['cache'] == TRUE) {
        $yamlData['parameters']['twig.config']['cache'] = FALSE;
        // Dump the modified array back into the YAML file.
        // 4 for indentation, 2 for spaces.
        file_put_contents($servicesFile, Yaml::dump($yamlData, 4, 2));
        $this->say("Twig caching disabled.");
      }
      else {
        $this->say("Skipping: Twig cache (already disabled).");
      }
    }
    catch (ParseException $e) {
      $this->say('Unable to parse the YAML string: ' . $e->getMessage());
    }

    // Ensure settings.local.php exists.
    $settings_file = $this->ensureSettingsLocalFileExists();
    // Read the 'settings.local.php' file content.
    $settings_content = file_get_contents($settings_file);

    foreach (self::CACHE_BINS as $label => $cache_bin) {
      // Check if the line is not commented out with a single #.
      if (strpos($settings_content, "# $cache_bin") !== FALSE) {
        $this->say('Disabling: ' . $label);
        // Uncomment the settings for the cache bin.
        $this->taskReplaceInFile($settings_file)
          ->from("# $cache_bin")
          ->to($cache_bin)
          ->run();
      }
      elseif (strpos($settings_content, $cache_bin) !== FALSE) {
        $this->say('Skipping: ' . $label . ' (already disabled)');
      }
    }

    // Clear Drupal cache.
    $this->taskExec('drush cr')->run();

    $this->say("Drupal caching disabled.");
  }

}
