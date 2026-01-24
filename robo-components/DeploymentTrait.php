<?php

namespace RoboComponents;

use Symfony\Component\Yaml\Yaml;

/**
 * Deployment tailored to Pantheon.io.
 */
trait DeploymentTrait {

  /**
   * The wait time between deployment checks in microseconds.
   *
   * @var int
   */
  public static int $deploymentWaitTime = 500000;

  /**
   * The maximum number of retries when waiting after git push.
   *
   * @var int
   */
  public static int $codeSyncWaitMaxRetries = 20;

  /**
   * The GitHub project slug.
   *
   * @var string
   */
  public static string $githubProject = 'Gizra/drupal-starter';

  /**
   * The name of the admin user (UID 1 is blocked by default).
   *
   * @var string
   */
  public static string $adminUser = 'AdminOne';

  /**
   * The files / directories to exclude from deployment.
   *
   * @var array|string[]
   */
  public static array $deploySyncExcludes = [
    '.bootstrap-fast.php',
    '.ddev',
    '.editorconfig',
    '.git',
    '.idea',
    '.pantheon',
    '.phpunit.result.cache',
    'ci-scripts',
    'pantheon.upstream.yml',
    'phpstan.neon',
    'phpstan-rules',
    'phpunit.xml.dist',
    'README.md',
    'RoboFile.php',
    'robo-components',
    'server.es.secrets.json',
    'pantheon-key.enc',
    'pantheon-key',
    'web/.csslintrc',
    'web/.eslintignore',
    'web/.eslintrc.json',
    'web/example.gitignore',
    'web/INSTALL.txt',
    'web/modules/README.txt',
    'web/profiles/README.txt',
    'web/README.md',
    'web/README.txt',
    'web/sites/default',
    'web/sites/simpletest',
    'web/sites/README.txt',
    'web/themes/README.txt',
    'web/themes/custom/server_theme/src',
    'web/themes/custom/server_theme/node_modules',
    'web/themes/custom/server_theme/package.json',
    'web/themes/custom/server_theme/package-lock.json',
    'web/themes/custom/server_theme/tailwind.config.js',
    'web/themes/custom/server_theme/postcss.config.js',
    'node_modules',
    'mass_patch.sh',
    'package.json',
    'package-lock.json',
    'web/libraries/font-awesome/js-packages',
    'web/libraries/font-awesome/metadata',
    'web/libraries/select2/src',
    'recipes',
    'yarn.lock',
    'patches.txt',
  ];

  /**
   * Get the full URL for a Pantheon environment with basic auth credentials.
   *
   * @param string $pantheon_environment
   *   The Pantheon environment (e.g., 'site.env').
   *
   * @return string
   *   The full URL with credentials if available.
   *
   * @throws \Exception
   */
  public function deployGetEnvironmentUrl(string $pantheon_environment): string {
    // Get lock info to check for HTTP basic auth.
    $lock_result = $this->taskExec("terminus lock:info $pantheon_environment --format=json")
      ->printOutput(FALSE)
      ->run();

    $lock_info = [];
    if ($lock_result->getExitCode() === 0) {
      $lock_output = trim($lock_result->getMessage());
      if (!empty($lock_output)) {
        $lock_info = json_decode($lock_output, TRUE);
      }
    }

    // Get domains associated with the environment.
    $domain_result = $this->taskExec("terminus domain:list $pantheon_environment --format=json")
      ->printOutput(FALSE)
      ->run();

    $base_url = '';
    if ($domain_result->getExitCode() === 0) {
      $domain_output = trim($domain_result->getMessage());
      if (!empty($domain_output)) {
        $domains = json_decode($domain_output, TRUE);
        if (!empty($domains) && is_array($domains)) {
          // Use the first domain in the list.
          $base_url = array_key_first($domains);
        }
      }
    }

    // Fallback to default pantheonsite.io domain.
    if (empty($base_url)) {
      $parts = explode('.', $pantheon_environment);
      if (count($parts) === 2) {
        $site_name = $parts[0];
        $env_name = $parts[1];
        $base_url = "$env_name-$site_name.pantheonsite.io";
      }
      else {
        throw new \Exception("Invalid Pantheon environment format: $pantheon_environment");
      }
    }

    // Check if environment is locked and has auth credentials.
    $is_locked = !empty($lock_info['locked']) && $lock_info['locked'] === TRUE;
    $has_auth = $is_locked && !empty($lock_info['username']) && !empty($lock_info['password']);

    if ($has_auth) {
      $username = $lock_info['username'];
      $password = $lock_info['password'];
      return "https://$username:$password@$base_url/";
    }

    return "https://$base_url/";
  }

  /**
   * Deploy a tag (specific release) to Pantheon.
   *
   * @param string $tag
   *   The tag name in the current repository.
   * @param string $branch_name
   *   The branch name from Pantheon repository.
   * @param string|null $commit_message
   *   Supply a custom commit message for the pantheon repo.
   *
   * @throws \Exception
   */
  public function deployTagPantheon(string $tag, string $branch_name = 'master', ?string $commit_message = NULL): void {
    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new \Exception('The working directory is dirty. Please commit or stash the pending changes. If you allowed new files in the .gitignore file, also double check composer.json scaffold section. https://www.drupal.org/docs/develop/using-composer/using-drupals-composer-scaffold');
    }

    // Check out the tag first to validate pantheon.yml exists on the
    // target branch.
    $this->taskExec("git checkout $tag")->run();

    // Full installation with dev dependencies as we need some of them for the
    // build.
    $this->taskExec("rm -rf vendor web/core web/libraries web/modules/contrib && composer install")->run();

    if (empty($commit_message)) {
      $commit_message = 'Release ' . $tag;
    }

    // Set default exit code to 0 (success).
    $exit = 0;
    try {
      $this->deployPantheon($branch_name, $commit_message);
    }
    catch (\Exception $e) {
      $this->yell('The deployment failed', 22, 'red');
      $this->say($e->getMessage());
      // Set exit code to 1 (error).
      $exit = 1;
    }
    // Check out the original branch regardless of success or failure.
    $this->taskExec("git checkout -")->run();
    exit($exit);
  }

  /**
   * Deploy to Pantheon.
   *
   * @param string $branch_name
   *   The branch name to commit to.
   * @param string|null $commit_message
   *   Supply a custom commit message for the pantheon repo.
   *   Falls back to: "Site update from [current_version]".
   *
   * @throws \Exception
   */
  public function deployPantheon(string $branch_name = 'master', ?string $commit_message = NULL): void {
    $pantheon_directory = '.pantheon';
    $deployment_version_path = $pantheon_directory . '/.deployment';

    if (!file_exists($pantheon_directory) || !is_dir($pantheon_directory)) {
      throw new \Exception('Clone the Pantheon artifact repository first into the .pantheon directory');
    }

    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_env = $branch_name == 'master' ? 'dev' : $branch_name;
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $pantheon_env;
    $result = $this->_exec("terminus connection:set $pantheon_terminus_environment git")->getExitCode();

    if ($result !== 0) {
      throw new \Exception("The Git mode could not be activated at $pantheon_terminus_environment, try to do it manually from the Pantheon dashboard.");
    }

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new \Exception('The project directory is dirty. Please commit any pending changes.');
    }

    $result = $this
      ->taskExec("cd $pantheon_directory && git status -s")
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new \Exception('The Pantheon directory is dirty. Please commit any pending changes.');
    }

    // Validate pantheon.yml has web_docroot: true.
    if (!file_exists($pantheon_directory . '/pantheon.yml')) {
      throw new \Exception("pantheon.yml is missing from the Pantheon directory ($pantheon_directory)");
    }

    $yaml = Yaml::parseFile($pantheon_directory . '/pantheon.yml');
    if (empty($yaml['web_docroot'])) {
      throw new \Exception("'web_docroot: true' is missing from pantheon.yml in Pantheon directory ($pantheon_directory)");
    }

    $this->_exec("cd $pantheon_directory && git checkout $branch_name");

    // We deal with versions as commit hashes.
    // The high-level goal is to prevent the auto-deploy process
    // to overwrite the code with an older version if the CI queue
    // swaps the order of two jobs, so they are not executed in
    // chronological order.
    $currently_deployed_version = NULL;
    if (file_exists($deployment_version_path)) {
      $currently_deployed_version = trim(file_get_contents($deployment_version_path));
    }

    $result = $this
      ->taskExec('git rev-parse HEAD')
      ->printOutput(FALSE)
      ->run();

    $current_version = trim($result->getMessage());

    if (!empty($currently_deployed_version)) {
      $result = $this
        ->taskExec('git cat-file -t ' . $currently_deployed_version)
        ->printOutput(FALSE)
        ->run();

      if ($result->getMessage() !== 'commit') {
        $this->yell(strtr('This current commit @current-commit cannot be deployed, as new commits have been created since, so we don\'t want to deploy an older version. Result was: @result', [
          '@current-commit' => $current_version,
          '@result' => $result->getMessage(),
        ]));
        throw new \Exception('Aborting the process to avoid going back in time.');
      }
    }

    // Ensure the dev dependencies are installed before compiling the theme in
    // case this is a retry.
    $this->taskExec('composer install')->run();

    // Compile theme.
    $this->themeCompile();

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', self::$deploySyncExcludes);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsync_exclude_string . $pantheon_directory")->getExitCode();
    if ($result !== 0) {
      throw new \Exception('File sync failed');
    }

    // The settings.pantheon.php is managed by Pantheon, there can be updates,
    // site-specific modifications belong to settings.php.
    $this->_exec("cp web/sites/default/settings.pantheon.php $pantheon_directory/web/sites/default/settings.php");

    // Prevent attackers to reach these standalone scripts.
    $this->_exec("rm -f $pantheon_directory/web/core/install.php");
    $this->_exec("rm -f $pantheon_directory/web/core/update.php");

    // Remove the dev dependencies before pushing up to Pantheon.
    $this->_exec("rm -rf $pantheon_directory/vendor");
    $this->_exec("(cd $pantheon_directory && composer install --no-dev && composer dump-autoload)");

    // Flag the current version in the artifact repo.
    file_put_contents($deployment_version_path, $current_version);

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
    // @todo Ignore it from rsync, but './.gitignore' didn't work.
    $this->_exec("cd $pantheon_directory && git checkout .gitignore");

    // Also we need to clean up gitignores that are deeper in the tree,
    // those can be troublemakers too, it also purges various Git helper
    // files that are irrelevant here.
    $this->_exec("cd $pantheon_directory && (find . | grep \"\.git\" | grep -v \"^./.git\"  |  xargs rm -rf || true)");

    $this->_exec("cd $pantheon_directory && git status");

    $commit_and_deploy_confirm = $this->confirm('Commit changes and deploy?', TRUE);
    if (!$commit_and_deploy_confirm) {
      $this->say('Aborted commit and deploy, you can do it manually');

      // The Pantheon repo is dirty, so check if we want to clean it up before
      // exit.
      $cleanup_pantheon_directory_confirm = $this->confirm("Revert any changes on $pantheon_directory directory (i.e. `git checkout .`)?");
      if (!$cleanup_pantheon_directory_confirm) {
        // Keep folder as is.
        return;
      }

      // We repeat "git clean" twice, as sometimes it seems that a single one
      // doesn't remove all directories.
      $this->_exec("cd $pantheon_directory && git checkout . && git clean -fd && git clean -fd && git status");

      return;
    }

    if (empty($commit_message)) {
      $tag = $this->taskExec("git tag --points-at HEAD")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty($tag)) {
        $commit_message = 'Site update from ' . $current_version;
      }
      else {
        $commit_message = "Site update from $tag ($current_version)";
      }
    }
    $commit_message = escapeshellarg($commit_message);
    $result = $this->taskExecStack()
      ->dir('.pantheon')
      ->exec('git pull --ff-only')
      ->exec('git pull')
      ->exec('git add .')
      ->exec("git commit -qam $commit_message")
      ->exec("git push --verbose origin HEAD:$branch_name")
      ->printOutput(TRUE)
      ->printMetadata(TRUE)
      ->stopOnFail()
      ->run();

    // We want to halt the deployment only where the commit push failed while
    // actually trying to push new code. If the deploy fails because some other
    // requirements (like existing content not properly removed) allow re-run
    // the deployment via the Continuous Integration UI.
    $nothing_to_commit = FALSE;
    if (str_contains($result->getMessage(), 'nothing to commit, working tree clean')) {
      $this->say('Nothing to commit, working tree clean');
      $nothing_to_commit = TRUE;
    }
    print $result->getMessage();

    if ($result->getExitCode() !== 0 && $nothing_to_commit === FALSE) {
      throw new \Exception('Pushing to the remote repository failed');
    }

    // Let's wait until the code is deployed to the environment.
    // This "git push" above is as async operation, so prevent invoking
    // for instance drush cim before the new changes are there.
    usleep(self::$deploymentWaitTime);

    $this->waitForCodeDeploy($pantheon_env);
    $this->deployPantheonSync($pantheon_env, FALSE);
  }

  /**
   * Waits until no code sync is running.
   *
   * @param string $env
   *   The environment to wait for.
   */
  protected function waitForCodeDeploy(string $env) {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $attempt = 0;
    $code_sync_completed = FALSE;
    do {
      $attempt++;
      $result = $this->taskExec("terminus workflow:list " . $pantheon_info['name'] . " --format=json --fields=env,status,workflow")->printOutput(FALSE)->run();
      if ($result->getExitCode() !== 0) {
        $this->yell('Getting workflow list failed');
        continue;
      }
      $result = json_decode($result->getMessage(), TRUE);
      $workflows = array_filter($result, function ($workflow) use ($env) {
        // When there's a git push, there's a "Sync code" workflow that
        // needs to be completed, before we can rely on the code being
        // present.
        return $workflow['env'] == $env && $workflow['status'] == 'running' && str_contains($workflow['workflow'], 'Sync ') !== FALSE;
      });
      $this->say(print_r($workflows, TRUE));
      $code_sync_completed = empty($workflows);
      usleep(self::$deploymentWaitTime);
    } while (!$code_sync_completed && $attempt < self::$codeSyncWaitMaxRetries);
  }

  /**
   * Get the Pantheon name and environment.
   *
   * @return array
   *   Array keyed by `name` and `env`.
   *
   * @throws \Exception
   */
  protected function getPantheonNameAndEnv() : array {
    $yaml_path = './.ddev/providers/pantheon.yaml';
    // This way we can use most commands natively, if we want.
    // The preferred, supported way is still via DDEV.
    // I had one case where I wanted to rely on the nameservers
    // defined by the host only - that could be one use-case.
    if (file_exists($yaml_path)) {
      $yaml = Yaml::parseFile($yaml_path);
    }
    else {
      $yaml = Yaml::parseFile('../' . $yaml_path);
    }
    if (empty($yaml['environment_variables']['project'])) {
      throw new \Exception("`environment_variables.project` is missing from .ddev/providers/pantheon.yaml");
    }

    $project = explode('.', $yaml['environment_variables']['project'], 2);
    if (count($project) !== 2) {
      throw new \Exception("`environment_variables.project` should be in the format of `yourproject.dev`");
    }

    return [
      'name' => $project[0],
      'env' => $project[1],
    ];

  }

  /**
   * Deploy site from one env to the other on Pantheon.
   *
   * @param string $env
   *   The environment to update.
   * @param bool $do_deploy
   *   Determine if 'terminus env:deploy' should be run on the given env.
   *
   * @throws \Exception
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
      // A repeat config import may be required. Run it in any case.
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")
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
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli --name=" . self::$adminUser)
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
      // On purpose, we do not halt the process here or make the build red.
      // Requirement errors are bad, we would like to know about them via
      // a GitHub message, but it should not abort a live deployment
      // for instance.
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
   * Check for requirement errors on the given environment.
   *
   * @param string $environment
   *   The environment to check.
   *
   * @throws \Exception
   */
  public function deployCheckRequirementErrors(string $environment): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $environment;
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
   * Install the site on specific env on Pantheon from scratch.
   *
   * Running this command via `ddev` will require terminus login inside ddev:
   * `ddev auth ssh`.
   *
   * @param string $env
   *   The environment to install.
   * @param string $pantheon_name
   *   The Pantheon site name.
   * @param array $options
   *   Extra options for this command.
   *
   * @option backup Will create a multidev environment named env-YYMMDD with the database and files of the environment being reinstalled.
   *
   * @throws \Exception
   */
  public function deployPantheonInstallEnv(string $env = 'qa', ?string $pantheon_name = NULL, array $options = ['backup' => FALSE]): void {
    $forbidden_envs = [
      'live',
    ];
    if (in_array($env, $forbidden_envs)) {
      throw new \Exception("Reinstalling the site on `$env` environment is forbidden.");
    }

    if ($pantheon_name === NULL) {
      $pantheon_info = $this->getPantheonNameAndEnv();
      $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;
    }
    else {
      $pantheon_terminus_environment = $pantheon_name . '.' . $env;
    }

    // This set of commands should work, so expecting no failures
    // (tend to invoke the same flow as DDEV's `config.local.yaml`).
    $task = $this
      ->taskExecStack()
      ->stopOnFail();

    // If --backup is specified, backup the environment before reinstalling it.
    if (!empty($options['backup'])) {
      // Example qa-231230, or test-231230.
      $backup_name = sprintf("%s-%s", $env, date('ymd'));
      $task->exec("terminus multidev:create $pantheon_terminus_environment $backup_name");
    }

    // Drupal checks is the settings.php is writable. With the connection
    // mode switch, we can fulfill this.
    $task
      ->exec("terminus connection:set $pantheon_terminus_environment sftp")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- si server --no-interaction --existing-config")
      ->exec("terminus connection:set $pantheon_terminus_environment git --yes")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm-enable default_content --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm-enable server_default_content --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm:uninstall server_default_content default_content --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- set-homepage")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli --name=" . self::$adminUser);

    $result = $task->run()->getExitCode();

    if ($result !== 0) {
      throw new \Exception("The site failed to install on Pantheon's `$env` environment.");
    }
  }

  /**
   * Prepares the repository to perform automatic deployment to Pantheon.
   *
   * @param string $token
   *   Terminus machine token: https://pantheon.io/docs/machine-tokens.
   * @param string $github_token
   *   Personal GitHub token:
   *   https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token.
   * @param string $github_deploy_branch
   *   The branch that should be pushed automatically to Pantheon. By default,
   *   it's 'main', the default GitHub branch for any new project.
   * @param string $pantheon_deploy_branch
   *   The branch at the artifact repo that should be the target of the
   *   deployment. As we typically deploy to QA, the default value here is 'qa',
   *   that multi-dev environment should be created by hand beforehand.
   *
   * @throws \Exception
   */
  public function deployConfigAutodeploy(string $token, string $github_token, string $github_deploy_branch = 'main', string $pantheon_deploy_branch = 'qa'): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $project_name = $pantheon_info['name'];

    // Generate SSH key for deployment.
    $result = $this->taskExec('ssh-keygen -t rsa -f pantheon-key -P ""')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The key generation failed.');
    }

    // Encrypt the SSH key for use in GitHub Actions.
    $result = $this->taskExec('openssl rand -hex 32')->printOutput(FALSE)->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('Failed to generate encryption key.');
    }
    $encryption_key = trim($result->getMessage());

    $result = $this->taskExec('openssl rand -hex 16')->printOutput(FALSE)->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('Failed to generate encryption IV.');
    }
    $encryption_iv = trim($result->getMessage());

    $result = $this->taskExec("openssl aes-256-cbc -K $encryption_key -iv $encryption_iv -in pantheon-key -out pantheon-key.enc")->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The encryption of the private key failed.');
    }

    $result = $this->taskExec("terminus connection:info $project_name.dev --fields='Git Command' --format=string | awk '{print $3}'")
      ->printOutput(FALSE)
      ->run();
    $pantheon_git_url = trim($result->getMessage());

    // Update GitHub Actions workflows if they exist.
    if (file_exists('.github/workflows/lint.template.yml')) {
      $this->_exec("cp .github/workflows/lint.template.yml .github/workflows/lint.yml");
      $this->taskReplaceInFile('.github/workflows/lint.yml')
        ->from('{{ GITHUB_DEPLOY_BRANCH }}')
        ->to($github_deploy_branch)
        ->run();
    }

    $result = $this->taskExec('git add pantheon-key.enc')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception("git add failed.");
    }

    $this->say("The project was prepared for automatic deployment to Pantheon using GitHub Actions");
    $this->say("");
    $this->say("Please complete the following steps:");
    $this->say("");
    $this->say("1. Add the following secrets to your GitHub repository:");
    $this->say("   - Go to: Settings → Secrets and variables → Actions → New repository secret");
    $this->say("   - PANTHEON_GIT_URL: " . $pantheon_git_url);
    $this->say("   - TERMINUS_TOKEN: " . $token);
    $this->say("   - ENCRYPTED_KEY: " . $encryption_key);
    $this->say("   - ENCRYPTED_IV: " . $encryption_iv);
    $this->say("   - GITHUB_TOKEN: (use the automatically provided token or your personal token)");
    $this->say("   - ROLLBAR_SERVER_TOKEN: (your Rollbar token if applicable)");
    $this->say("");
    $this->say("2. Add the SSH public key to the Pantheon account:");
    $this->say("   - Key location: pantheon-key.pub");
    $this->say("   - Instructions: https://pantheon.io/docs/ssh-keys");
    $this->say("");
    $this->say("3. Review and commit the encrypted key file (pantheon-key.enc)");
    $this->say("");
    $this->say("4. Ensure nested docroot is configured: https://pantheon.io/docs/nested-docroot");
  }

  /**
   * Posts a comment on the GitHub issue that the code got deployed to Pantheon.
   *
   * @param string $pantheon_environment
   *   The Pantheon environment where the code was deployed.
   * @param string $issue_comment
   *   The comment to post on the issue.
   */
  public function deployNotify(string $pantheon_environment = 'qa', string $issue_comment = '') {
    if (!empty($issue_comment)) {
      $data = ['body' => $issue_comment];
      $issue_comment = json_encode($data);
    }
    $git_commit_message = getenv('GITHUB_COMMIT_MESSAGE');
    if (strstr($git_commit_message, 'Merge pull request') === FALSE && strstr($git_commit_message, ' (#') === FALSE) {
      $this->say($git_commit_message);
      return;
    }

    $issue_matches = [];
    $issue_numbers = [];
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
      // Retrieve the issue number from the PR description via GitHub CLI.
      $pr_number = $pr_matches[1][0];
      $pr_body = $this->taskExec("gh pr view $pr_number --json body --jq .body")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty(trim($pr_body))) {
        $this->say("Could not determine the issue number from the PR: $git_commit_message");
        return;
      }
      // The issue number should be the "#1234"-like reference in the PR body.
      preg_match_all('!#([0-9]+)\s+!', $pr_body, $issue_matches);
      if (!isset($issue_matches[1][0])) {
        $this->say("Could not determine the issue number from the PR description: $pr_body");
        return;
      }
      foreach ($issue_matches[1] as $issue_match) {
        $issue_numbers[] = $issue_match;
      }
    }
    else {
      $issue_numbers[] = $issue_matches[1][0];
    }

    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $pantheon_environment;

    // Check if the repository is private via GitHub CLI.
    $is_private = $this->taskExec("gh repo view --json isPrivate --jq .isPrivate")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $is_private = trim($is_private) === 'true';
    if ($is_private) {
      $quick_link = $this->deployGetEnvironmentUrl($pantheon_terminus_environment);
    }
    else {
      // Otherwise, just link the environment.
      $quick_link = "https://" . $pantheon_environment . "-" . $pantheon_info['name'] . ".pantheonsite.io";
    }

    if (empty($issue_comment)) {
      if (empty($pr_number)) {
        $comment_body = "The latest merged PR just got deployed successfully to Pantheon [`$pantheon_environment`]($quick_link) environment";
      }
      else {
        $comment_body = "The latest merged PR #$pr_number just got deployed successfully to Pantheon [`$pantheon_environment`]($quick_link) environment";
      }
    }
    else {
      // Extract body from JSON if issue_comment was provided.
      $comment_data = json_decode($issue_comment, TRUE);
      $comment_body = $comment_data['body'] ?? $issue_comment;
    }

    foreach ($issue_numbers as $issue_number) {
      $result = $this->taskExec("gh issue comment $issue_number --body " . escapeshellarg($comment_body))
        ->printOutput(FALSE)
        ->run();
      $exit_code = $result->getExitCode();
      if ($exit_code) {
        throw new \Exception("Could not notify GitHub of the deployment, GitHub CLI error: " . $result->getMessage());
      }
    }
  }

}
