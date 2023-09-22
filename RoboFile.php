<?php

declare(strict_types = 1);

use Drupal\Core\DrupalKernel;
use GizraRobo\PhpcsTasks;
use GizraRobo\ReleaseNotesTasks;
use GizraRobo\BootstrapTasks;
use GizraRobo\DeploymentTasks;
use GizraRobo\ElasticSearchTasks;
use GizraRobo\ThemeTasks;
use GizraRobo\TranslationTasks;
use Robo\Tasks;
use Symfony\Component\HttpFoundation\Request;

$GLOBALS['drupal_autoloader'] = require_once 'web/autoload.php';

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use BootstrapTasks;
  use DeploymentTasks;
  use ElasticSearchTasks;
  use TranslationTasks;
  use PhpcsTasks;
  use ReleaseNotesTasks;
  use ThemeTasks;

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

}
