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
  public function deployPantheonSync(string $env = 'test', bool $do_deploy = TRUE): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    $task = $this->taskExecStack()
      ->stopOnFail();

    if ($do_deploy) {
      $task->exec("terminus env:deploy $pantheon_terminus_environment");
    }

    $result = $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- updb --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")
      // Pre-warm the Twig template cache so the first anonymous
      // request after deploy gets a cached hit.
      ->exec("terminus remote:drush $pantheon_terminus_environment -- twig:compile")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- deploy:hook --no-interaction")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      $message = "The site could not be fully updated at Pantheon at $env. Try fixing manually.";
      $this->deployNotify($env, $message);
      throw new \Exception($message);
    }

    $result = $this->localeImport(FALSE, $env);
    if ($result->getExitCode() !== 0) {
      $message = "The deployment went well to $env, but the locale import failed. Try to perform manually later.";
      $this->deployNotify($env, $message);
      throw new \Exception($message);
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- user-block --uid=1")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      $message = "The code deploy went well to $env, but blocking #1 user failed.";
      $this->deployNotify($env, $message);
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-r")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-i")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      $message = "The deployment went well to $env, but the re-indexing to ElasticSearch failed. Try to perform manually later.";
      $this->deployNotify($env, $message);
      throw new \Exception($message);
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli --name=" . $this->getAdminUser())
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      $message = "Could not generate a login link at $env. Try again manually or check earlier errors.";
      $this->deployNotify($env, $message);
      throw new \Exception($message);
    }

    try {
      $this->deployCheckRequirementErrors($env);
    }
    catch (\Exception $e) {
      $message = "The deployment went well to $env, but there are requirement errors. Address these:" . PHP_EOL . $e->getMessage();
      try {
        $this->deployNotify($env, $message);
      }
      catch (\Exception $e) {
        $this->yell($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogFile(string $env) {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $site_env = $pantheon_info['name'] . ".$env";

    // Fetch all SFTP connection fields in a single terminus call.
    $result = $this->taskExec("terminus connection:info $site_env --format=json --fields=sftp_username,sftp_host,sftp_port")
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      throw new \Exception("Could not get SFTP connection information for $site_env");
    }

    $conn = json_decode(trim($result->getMessage()), TRUE);
    $sftp_username = trim($conn['sftp_username'] ?? '');
    $sftp_host = trim($conn['sftp_host'] ?? '');
    $sftp_port = trim($conn['sftp_port'] ?? '') ?: '2222';

    if (empty($sftp_username) || empty($sftp_host)) {
      throw new \Exception("Could not get SFTP connection information for $site_env");
    }

    // A Pantheon environment can run on several application
    // containers, each keeping its own nginx logs. Resolve every
    // container IP and aggregate their logs.
    // @see https://docs.pantheon.io/guides/logs-pantheon/automate-log-downloads
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
   * {@inheritdoc}
   */
  public function deployCheckRequirementErrors(string $environment): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $environment;

    // Drush CLI calls have no Drupal route match, so
    // UpdateManager::projectStorage() always returns stale cached
    // project data, which can produce false "Not secure!" warnings.
    // Clear it before checking requirements.
    $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- php:eval \"\\Drupal::keyValueExpirable('update')->delete('update_project_projects');\"")
      ->run();

    $task = $this->taskExecStack()
      ->stopOnFail();
    $output = $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- rq --format=json")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    if (empty($output)) {
      throw new \Exception("Cannot get requirement errors via terminus, try to authenticate first: ddev auth ssh && ddev . terminus login --machine-token=[TOKEN]");
    }

    $errors = [];
    $parsed_output = json_decode($output, TRUE);

    if (!is_array($parsed_output)) {
      throw new \Exception("Cannot parse the response of terminus: " . serialize($parsed_output));
    }

    $exclude = (string) getenv('DEPLOY_EXCLUDE_WARNING');
    $exclude_list = explode('|', $exclude);

    foreach ($parsed_output as $requirement) {
      if ($requirement['severity'] !== 'Error') {
        continue;
      }
      if (in_array($requirement['title'], $exclude_list) || in_array($requirement['value'], $exclude_list)) {
        // A warning we decided to exclude.
        continue;
      }
      $errors[] = '## ' . trim($requirement['title']) . "\n" . trim($requirement['value']);
    }
    if (empty($errors)) {
      return;
    }
    throw new \Exception(implode("\n\n", $errors));
  }

  /**
   * Finds the latest open release issue to notify about live deployments.
   *
   * Uses the "release" label to identify release issues, so the match
   * is robust regardless of title wording.
   *
   * @param string $github_token
   *   The GitHub API token.
   *
   * @return int|null
   *   The issue number, or NULL if none found.
   */
  protected function deployFindLatestReleaseIssue(string $github_token): ?int {
    $result = $this->taskExec("curl -H \"Authorization: token $github_token\" \"https://api.github.com/repos/" . $this->getGithubProject() . "/issues?state=open&labels=release&sort=created&direction=desc&per_page=1\"")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $issues = json_decode($result);
    if (!is_array($issues) || empty($issues)) {
      return NULL;
    }

    return (int) $issues[0]->number;
  }

  /**
   * {@inheritdoc}
   */
  public function deployNotify(string $pantheon_environment = 'qa', string $issue_comment = '') {
    if (!empty($issue_comment)) {
      $data = ['body' => $issue_comment];
      $issue_comment = json_encode($data);
    }
    $github_token = getenv('GITHUB_TOKEN');

    $issue_numbers = [];
    if ($pantheon_environment === 'live') {
      // Live deploys are triggered from a release tag, so the commit
      // message does not point back to the originating issue. Post to
      // the release issue instead.
      $release_issue_number = $this->deployFindLatestReleaseIssue($github_token);
      if ($release_issue_number !== NULL) {
        $issue_numbers[] = $release_issue_number;
      }
    }

    if (empty($issue_numbers)) {
      $git_commit_message = getenv('GITHUB_COMMIT_MESSAGE');
      if (strstr($git_commit_message, 'Merge pull request') === FALSE && strstr($git_commit_message, ' (#') === FALSE) {
        $this->say($git_commit_message);
        return;
      }

      $issue_matches = [];
      // If the PR was simply merged, then we have this:
      preg_match_all('!from [a-zA-Z-_0-9]+/([0-9]+)!', $git_commit_message, $issue_matches);
      if (!isset($issue_matches[1][0])) {
        $this->say("Could not determine the issue number from the commit message name: $git_commit_message");

        // If the PR was merged with a squash, then we have this:
        // blah blah (#1234)
        // Where 1234 is the PR number.
        $pr_matches = [];
        preg_match_all('!\(#([0-9]+)\)!', $git_commit_message, $pr_matches);
        if (!isset($pr_matches[0][0])) {
          $this->say("Could not determine the PR number from the commit message: $git_commit_message");
          return;
        }
        // Retrieve the issue number from the PR description via GitHub API.
        $pr_number = $pr_matches[1][0];
        $pr = $this->taskExec("curl -H \"Authorization: token $github_token\" https://api.github.com/repos/" . $this->getGithubProject() . "/pulls/$pr_number")
          ->printOutput(FALSE)
          ->run()
          ->getMessage();
        $pr = json_decode($pr);
        if (!isset($pr->body)) {
          $this->say("Could not determine the issue number from the PR: $git_commit_message");
          return;
        }
        // The issue number should be the "#1234"-like reference in the PR body.
        preg_match_all('!#([0-9]+)\s+!', $pr->body, $issue_matches);
        if (!isset($issue_matches[1][0])) {
          $this->say("Could not determine the issue number from the PR description: $pr->body");
          return;
        }
        foreach ($issue_matches[1] as $issue_match) {
          $issue_numbers[] = $issue_match;
        }
      }
      else {
        $issue_numbers[] = $issue_matches[1][0];
      }
    }

    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $pantheon_environment;

    // Let's figure out if the repository is public or not via GitHub API.
    $repo = $this->taskExec("curl -H \"Authorization: token $github_token\" https://api.github.com/repos/" . $this->getGithubProject())
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $repo = json_decode($repo);
    if (!isset($repo->private)) {
      $this->yell("Could not determine if the repository is private or not.");
      return;
    }
    if ($repo->private) {
      $quick_link = $this->deployGetEnvironmentUrl($pantheon_terminus_environment);
    }
    else {
      // Otherwise, just link the environment.
      $quick_link = "https://" . $pantheon_environment . "-" . $pantheon_info['name'] . ".pantheonsite.io";
    }

    if (empty($issue_comment)) {
      if (empty($pr_number)) {
        $issue_comment = "{\"body\": \"The latest merged PR just got deployed successfully to Pantheon [`$pantheon_environment`]($quick_link) environment\"}";
      }
      else {
        $issue_comment = "{\"body\": \"The latest merged PR #$pr_number just got deployed successfully to Pantheon [`$pantheon_environment`]($quick_link) environment\"}";
      }
    }
    foreach ($issue_numbers as $issue_number) {
      $result = $this->taskExec("curl -X POST -H 'Authorization: token $github_token' -d '$issue_comment' https://api.github.com/repos/" . $this->getGithubProject() . "/issues/$issue_number/comments")
        ->printOutput(FALSE)
        ->run();
      $exit_code = $result->getExitCode();
      if ($exit_code) {
        throw new \Exception("Could not notify GitHub of the deployment, GitHub API error: " . $result->getMessage());
      }
    }
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
    }
    catch (\Exception $e) {
      // Do not fail, there are several commands that do not need Drupal.
      $this->yell($e->getMessage());
    }
    finally {
      chdir(__DIR__);
    }
  }

}
