<?php

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Robo\Tasks;
use RoboComponents\AutoUpdateTrait;
use RoboComponents\BootstrapTrait;
use RoboComponents\DeploymentTrait;
use RoboComponents\PantheonRemoteTrait;
use RoboComponents\PhpcsTrait;
use RoboComponents\ProjectConfigTrait;
use RoboComponents\ReleaseNotesTrait;
use RoboComponents\SecurityTrait;
use RoboComponents\ThemeTrait;
use RoboComponents\TranslationManagement\ExportFromConfig;
use RoboComponents\TranslationManagement\ImportToConfig;
use RoboComponents\TranslationManagement\ImportToUi;
use Symfony\Component\HttpFoundation\Request;

$GLOBALS['drupal_autoloader'] = require_once 'web/autoload.php';

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  use AutoUpdateTrait;
  use BootstrapTrait;
  use DeploymentTrait;
  use ExportFromConfig;
  use ImportToConfig;
  use ImportToUi;
  use PhpcsTrait;
  use PantheonRemoteTrait;
  use ProjectConfigTrait;
  use ReleaseNotesTrait;
  use SecurityTrait;
  use ThemeTrait;

  /**
   * {@inheritdoc}
   */
  protected function getThemeName(): string {
    return 'server_theme';
  }

  /**
   * {@inheritdoc}
   */
  protected function getGithubProject(): string {
    return 'Gizra/drupal-starter';
  }

  /**
   * Defines a list of languages installed on the site.
   *
   * Edit as languages are installed/removed. This is used for translation
   * management. Do not include 'en', 'und' or 'zxx'.
   */
  protected function getInstalledLanguages(): array {
    return [
      'ar',
      'es',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getLogFile(string $env) {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $site_env = $pantheon_info['name'] . ".$env";

    // A Pantheon environment can run on several application
    // containers, each keeping its own nginx logs. Resolve every
    // container IP and aggregate their logs.
    $sftp_username = trim($this->getTerminusConnectionField($site_env, 'sftp_username'));
    $sftp_host = trim($this->getTerminusConnectionField($site_env, 'sftp_host'));
    $sftp_port = trim($this->getTerminusConnectionField($site_env, 'sftp_port')) ?: '2222';

    if (empty($sftp_username) || empty($sftp_host)) {
      throw new \Exception("Could not get SFTP connection information for $site_env");
    }

    $app_server_ips = gethostbynamel($sftp_host);
    if (empty($app_server_ips)) {
      $app_server_ips = [$sftp_host];
    }

    $this->say(sprintf('Found %d application container(s) for %s.', count($app_server_ips), $site_env));

    if (file_exists(self::$accessLogPath)) {
      unlink(self::$accessLogPath);
    }

    $container_logs = [];
    foreach ($app_server_ips as $index => $app_server_ip) {
      $container_log = "/tmp/nginx-access-$index.log";
      $this->_exec("sftp -o Port=$sftp_port -o StrictHostKeyChecking=no $sftp_username@$app_server_ip <<EOF
cd logs/nginx
get nginx-access.log $container_log
EOF
");
      if (file_exists($container_log)) {
        $container_logs[] = $container_log;
      }
    }

    if (empty($container_logs)) {
      $this->say('Try to use "ddev auth ssh" first to fix this issue. It will let DDEV download the logs via SFTP with the keys of the host system.');
      $this->say('https://ddev.readthedocs.io/en/stable/users/usage/cli/#ssh-into-containers');
      throw new \Exception('Failed to download the logfiles');
    }

    foreach ($container_logs as $container_log) {
      $this->taskExec('cat ' . escapeshellarg($container_log) . ' >> ' . escapeshellarg(self::$accessLogPath))
        ->run();
      unlink($container_log);
    }
  }

  /**
   * Reads a single field from `terminus connection:info`.
   *
   * @param string $site_env
   *   The Pantheon site and environment, e.g. `myproject.live`.
   * @param string $field
   *   The connection field to read, e.g. `sftp_host`.
   *
   * @return string
   *   The field value, or empty string on failure.
   */
  protected function getTerminusConnectionField(string $site_env, string $field): string {
    $result = $this->taskExec("terminus connection:info $site_env --field=$field")
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      return '';
    }

    return $result->getMessage();
  }

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
