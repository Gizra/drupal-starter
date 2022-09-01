<?php

declare(strict_types = 1);

use Lurker\Event\FilesystemEvent;
use Robo\ResultData;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Robo commands.
 */
class RoboFile extends Tasks {

  const THEME_NAME = 'server_theme';

  const THEME_BASE = 'web/themes/custom/' . self::THEME_NAME;

  /**
   * The wait time between deployment checks in microseconds.
   */
  const DEPLOYMENT_WAIT_TIME = 500000;

  /**
   * ElasticSearch index prefix.
   *
   * @var string
   */
  private static string $indexPrefix = 'elasticsearch_index_pantheon_';

  /**
   * Compile the theme.
   *
   * @param bool $optimize
   *   Indicate whether to optimize during compilation. Default: FALSE.
   */
  private function doThemeCompile(bool $optimize = FALSE): void {
    $directories = [
      'js',
      'images',
    ];

    // Cleanup and create directories.
    $this->_deleteDir(self::THEME_BASE . '/dist');
    foreach ($directories as $dir) {
      $directory = self::THEME_BASE . '/dist/' . $dir;
      $this->_mkdir($directory);
    }

    $theme_dir = self::THEME_BASE;

    // Make sure we have all the node packages.
    $this->_exec("cd $theme_dir && npm install");

    $result = $this->_exec("cd $theme_dir && npx postcss ./src/pcss/style.pcss --output=./dist/css/style.css");

    if ($result->getExitCode() !== 0) {
      $this->taskCleanDir(['dist/css']);
      return;
    }

    // Javascript.
    if ($optimize) {
      // Minify the JS files.
      foreach (glob(self::THEME_BASE . '/src/js/*.js') as $js_file) {

        $to = $js_file;
        $to = str_replace('/src/', '/dist/', $to);

        $this->taskMinify($js_file)
          ->to($to)
          ->type('js')
          ->singleLine(TRUE)
          ->keepImportantComments(FALSE)
          ->run();
      }
    }
    else {
      $this->_copyDir(self::THEME_BASE . '/src/js', self::THEME_BASE . '/dist/js');
    }

    // Images - Copy everything first.
    $this->_copyDir(self::THEME_BASE . '/src/images', self::THEME_BASE . '/dist/images');

    // Then for the formats that we can optimize, perform it.
    if ($optimize) {
      $input = [
        self::THEME_BASE . '/src/images/*.jpg',
        self::THEME_BASE . '/src/images/*.png',
      ];

      $this->taskImageMinify($input)
        ->to(self::THEME_BASE . '/dist/images/')
        ->run();

      // Compress all SVGs.
      $this->themeSvgCompress();
    }

    $this->_exec('drush cache:rebuild');
  }

  /**
   * Compile the theme (optimized).
   */
  public function themeCompile(): void {
    $this->say('Compiling (optimized).');
    $this->doThemeCompile(TRUE);
  }

  /**
   * Compile the theme.
   *
   * Non-optimized.
   */
  public function themeCompileDebug(): void {
    $this->say('Compiling (non-optimized).');
    $this->doThemeCompile();
  }

  /**
   * Compress SVG files in the "dist" directories.
   *
   * This function is being called as part of `theme:compile`.
   *
   * @return \Robo\ResultData|null
   *   If there was an error a result data object is returned. Or void if
   *   successful.
   *
   * @see doThemeCompile()
   */
  public function themeSvgCompress(): ?ResultData {
    $directories = [
      './dist/images',
    ];

    $error_code = NULL;

    foreach ($directories as $directory) {
      // Check if SVG files exists in this directory.
      $finder = new Finder();
      $finder
        ->in(self::THEME_BASE . '/' . $directory)
        ->files()
        ->name('*.svg');

      if (!$finder->hasResults()) {
        // No SVG files.
        continue;
      }

      $result = $this->_exec("cd " . self::THEME_BASE . " && npx svgo $directory/*.svg");
      if (empty($error_code) && !$result->wasSuccessful()) {
        $error_code = $result->getExitCode();
      }
    }

    if (!empty($error_code)) {
      return new ResultData($error_code, '`svgo` failed to run.');
    }
    return NULL;
  }

  /**
   * Directories that should be watched for the theme.
   *
   * @return array
   *   List of directories.
   */
  protected function monitoredThemeDirectories(): array {
    return [
      self::THEME_BASE . '/src',
    ];
  }

  /**
   * Watch the theme and compile on change (optimized).
   */
  public function themeWatch(): void {
    $this->say('Compiling and watching (optimized).');
    $this->doThemeCompile(TRUE);
    foreach ($this->monitoredThemeDirectories() as $directory) {
      $this->taskWatch()
        ->monitor(
          $directory,
          function () {
            $this->doThemeCompile(TRUE);
          },
          FilesystemEvent::ALL
        )->run();
    }
  }

  /**
   * Watch the theme path and compile on change (non-optimized).
   */
  public function themeWatchDebug(): void {
    $this->say('Compiling and watching (non-optimized).');
    $this->doThemeCompile();
    foreach ($this->monitoredThemeDirectories() as $directory) {
      $this->taskWatch()
        ->monitor(
          $directory,
          function () {
            $this->doThemeCompile();
          },
          FilesystemEvent::ALL
        )->run();
    }
  }

  /**
   * Deploy a tag (specific release) to Pantheon.
   *
   * @param string $tag
   *   The tag name in the current repository.
   * @param string $branch_name
   *   The branch name from Pantheon repository. Default: master
   * @param string|null $commit_message
   *   Supply a custom commit message for the pantheon repo.
   *   Default: "Release [tag]".
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
      throw new Exception('The working directory is dirty. Please commit or stash the pending changes.');
    }

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
    catch (Exception $e) {
      $this->yell('The deployment failed', 22, 'red');
      $this->say($e->getMessage());
      // Set exit code to 1 (error).
      $exit = 1;
    }
    // Check out the original branch regardless of success or failure.
    $this->taskExec("git checkout -")->run();
    // Exit.
    $this->taskExec("exit $exit")->run();
  }

  /**
   * Deploy to Pantheon.
   *
   * @param string $branch_name
   *   The branch name to commit to. Default: master.
   * @param string|null $commit_message
   *   Supply a custom commit message for the pantheon repo.
   *   Default: "Site update from [current_version]".
   *
   * @throws \Exception
   */
  public function deployPantheon(string $branch_name = 'master', ?string $commit_message = NULL): void {
    $pantheon_directory = '.pantheon';
    $deployment_version_path = $pantheon_directory . '/.deployment';

    if (!file_exists($pantheon_directory) || !is_dir($pantheon_directory)) {
      throw new Exception('Clone the Pantheon artifact repository first into the .pantheon directory');
    }

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new Exception('The Pantheon directory is dirty. Please commit any pending changes.');
    }

    $result = $this
      ->taskExec("cd $pantheon_directory && git status -s")
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new Exception('The Pantheon directory is dirty. Please commit any pending changes.');
    }

    // Validate pantheon.yml has web_docroot: true.
    if (!file_exists($pantheon_directory . '/pantheon.yml')) {
      throw new Exception("pantheon.yml is missing from the Pantheon directory ($pantheon_directory)");
    }

    $yaml = Yaml::parseFile($pantheon_directory . '/pantheon.yml');
    if (empty($yaml['web_docroot'])) {
      throw new Exception("'web_docroot: true' is missing from pantheon.yml in Pantheon directory ($pantheon_directory)");
    }

    $this->_exec("cd $pantheon_directory && git checkout $branch_name");

    // We deal with versions as commit hashes.
    // The high-level goal is to prevent the auto-deploy process
    // to overwrite the code with an older version if the Travis queue
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
        $this->yell(strtr('This current commit @current-commit cannot be deployed, since new commits have been created since, so we don\'t want to deploy an older version.', [
          '@current-commit' => $current_version,
        ]));
        $this->yell('Aborting the process to avoid going back in time.');
        return;
      }
    }

    // Compile theme.
    $this->themeCompile();

    // Remove the dev dependencies before pushing up to Pantheon.
    $this->taskExec("composer install --no-dev")->run();

    $rsync_exclude = [
      '.bootstrap-fast.php',
      '.ddev',
      '.editorconfig',
      '.git',
      '.gitpod.Dockerfile',
      '.gitpod.yml',
      '.idea',
      '.pantheon',
      '.phpunit.result.cache',
      '.travis.yml',
      'ci-scripts',
      'drush/drush.yml',
      'pantheon.yml',
      'pantheon.upstream.yml',
      'phpstan.neon',
      'phpunit.xml.dist',
      'README.md',
      'RoboFile.php',
      'server.es.secrets.json',
      'travis-key.enc',
      'travis-key',
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
    ];

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', $rsync_exclude);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsync_exclude_string . $pantheon_directory")->getExitCode();
    if ($result !== 0) {
      throw new Exception('File sync failed');
    }

    // The settings.pantheon.php is managed by Pantheon, there can be updates, site-specific modifications
    // belong to settings.php.
    $this->_exec("cp web/sites/default/settings.pantheon.php $pantheon_directory/web/sites/default/settings.php");

    // Flag the current version in the artifact repo.
    file_put_contents($deployment_version_path, $current_version);

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
    // @todo: Ignore it from rsync, but './.gitignore' didn't work.
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
      $commit_message = 'Site update from ' . $current_version;
    }
    $commit_message = escapeshellarg($commit_message);
    $result = $this->_exec("cd $pantheon_directory && git pull && git add . && git commit -am $commit_message && git push")->getExitCode();
    if ($result !== 0) {
      throw new Exception('Pushing to the remote repository failed');
    }

    // Let's wait until the code is deployed to the environment.
    // This "git push" above is as async operation, so prevent invoking
    // for instance drush cim before the new changes are there.
    usleep(self::DEPLOYMENT_WAIT_TIME);
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_env = $branch_name == 'master' ? 'dev' : $branch_name;

    do {
      $code_sync_completed = $this->_exec("terminus workflow:list " . $pantheon_info['name'] . " --format=csv | grep " . $pantheon_env . " | grep Sync | awk -F',' '{print $5}' | grep running")->getExitCode();
      usleep(self::DEPLOYMENT_WAIT_TIME);
    } while (!$code_sync_completed);
    $this->deployPantheonSync($pantheon_env, FALSE);
  }

  /**
   * Get the Pantheon name and environment.
   *
   * @return array
   *   Array keyed by `name` and `env`.
   * @throws \Exception
   */
  protected function getPantheonNameAndEnv() : array {
    $yaml = Yaml::parseFile('./.ddev/providers/pantheon.yaml');
    if (empty($yaml['environment_variables']['project'])) {
      throw new Exception("`environment_variables.project` is missing from .ddev/providers/pantheon.yaml");
    }

    $project = explode('.', $yaml['environment_variables']['project'], 2);
    if (count($project) !== 2) {
      throw new Exception("`environment_variables.project` should be in the format of `yourproject.dev`");
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
   *   The environment to update. Default: test.
   * @param bool $do_deploy
   *   Determine if 'terminus env:deploy' should be run on the given env.
   *   Default: TRUE.
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
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new Exception('The site could not be fully updated at Pantheon. Try "ddev robo deploy:pantheon-install-env" manually.');
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-r")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-i")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new Exception('The deployment went well, but the re-indexing to ElasticSearch failed. Try to perform manually later.');
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new Exception('Could not generate a login link. Try again manually or check earlier errors.');
    }
  }

  /**
   * Install the site on specific env on Pantheon from scratch.
   *
   * Running this command via `ddev` will require terminus login inside ddev:
   * `ddev auth ssh`.
   *
   * @param string $env
   *   The environment to install. Default: qa.
   *
   * @throws \Exception
   */
  public function deployPantheonInstallEnv(string $env = 'qa'): void {
    $forbidden_envs = [
      'live',
    ];
    if (in_array($env, $forbidden_envs)) {
      throw new Exception("Reinstalling the site on `$env` environment is forbidden.");
    }

    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    // This set of commands should work, so expecting no failures
    // (tend to invoke the same flow as DDEV's `config.local.yaml`).
    $task = $this
      ->taskExecStack()
      ->stopOnFail();

    $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- si server --no-interaction --existing-config")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- en server_migrate --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- migrate:import --group=server")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm:uninstall migrate")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli");

    $result = $task->run()->getExitCode();

    if ($result !== 0) {
      throw new Exception("The site failed to install on Pantheon's `$env` environment.");
    }
  }

  /**
   * Perform a Code sniffer test, and fix when applicable.
   *
   * @return \Robo\ResultData|null
   *   If there was an error a result data object is returned. Or null if
   *   successful.
   */
  public function phpcs(): ?ResultData {
    $standards = [
      'Drupal',
      'DrupalPractice',
    ];

    $commands = [
      'phpcbf',
      'phpcs',
    ];

    $directories = [
      'modules/custom',
      'themes/custom',
      'profiles/custom',
    ];

    $error_code = NULL;

    foreach ($directories as $directory) {
      foreach ($standards as $standard) {
        $arguments = "--standard=$standard -p --ignore=" . self::THEME_NAME . "/dist,node_modules --colors --extensions=php,module,inc,install,test,profile,theme,css,yaml,txt,md";

        foreach ($commands as $command) {
          $result = $this->_exec("cd web && ../vendor/bin/$command $directory $arguments");
          if (empty($error_code) && !$result->wasSuccessful()) {
            $error_code = $result->getExitCode();
          }
        }
      }
    }

    if (!empty($error_code)) {
      return new ResultData($error_code, 'PHPCS found some issues');
    }
    return NULL;
  }

  /**
   * Prepares the repository to perform automatic deployment to Pantheon.
   *
   * @param string $token
   *   Terminus machine token: https://pantheon.io/docs/machine-tokens.
   * @param string $github_token
   *   Personal GitHub token (Travis auth):
   *   https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token
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

    if (empty(shell_exec("which travis"))) {
      // We do not bake it into the Docker image to save on disk space.
      // We rarely need this operation, also not all the developers
      // will use it.
      $result = $this->taskExecStack()
        ->exec('sudo apt update')
        ->exec('sudo apt install ruby ruby-dev make g++ --yes')
        ->exec('sudo gem install travis --no-document')
        ->stopOnFail()
        ->run()
        ->getExitCode();

      if ($result !== 0) {
        throw new Exception('The installation of the dependencies failed.');
      }
    }

    $result = $this->taskExec('ssh-keygen -f travis-key -P ""')->run();
    if ($result->getExitCode() !== 0) {
      throw new Exception('The key generation failed.');
    }

    $result = $this->taskExec('travis login --pro --github-token="' . $github_token . '"')->run();
    if ($result->getExitCode() !== 0) {
      throw new Exception('The authentication with GitHub via Travis CLI failed.');
    }

    $result = $this->taskExec('travis encrypt-file travis-key --add --no-interactive --pro')
      ->run();
    if ($result->getExitCode() !== 0) {
      throw new Exception('The encryption of the private key failed.');
    }

    $result = $this->taskExec('travis encrypt TERMINUS_TOKEN="' . $token . '" --add --no-interactive --pro')
      ->run();
    if ($result->getExitCode() !== 0) {
      throw new Exception('The encryption of the Terminus token failed.');
    }

    $result = $this->taskExec("terminus connection:info $project_name.dev --fields='Git Command' --format=string | awk '{print $3}'")
      ->printOutput(FALSE)
      ->run();
    $pantheon_git_url = trim($result->getMessage());
    $this->taskReplaceInFile('.travis.yml')
      ->from('{{ PANTHEON_GIT_URL }}')
      ->to($pantheon_git_url)
      ->run();
    $this->taskReplaceInFile('.travis.yml')
      ->from('{{ PANTHEON_DEPLOY_BRANCH }}')
      ->to($pantheon_deploy_branch)
      ->run();
    $this->taskReplaceInFile('.travis.yml')
      ->from('{{ GITHUB_DEPLOY_BRANCH }}')
      ->to($github_deploy_branch)
      ->run();

    $result = $this->taskExec('git add .travis.yml travis-key.enc')->run();
    if ($result->getExitCode() !== 0) {
      throw new Exception("git add failed.");
    }
    $this->say("The project was prepared for the automatic deployment to Pantheon");
    $this->say("Review the changes and make a commit from the added files.");
    $this->say("Add the SSH key to the Pantheon account: https://pantheon.io/docs/ssh-keys .");
    $this->say("Add the SSH key to the GitHub project as a deploy key: https://docs.github.com/en/developers/overview/managing-deploy-keys .");
    $this->say("Convert the project to nested docroot: https://pantheon.io/docs/nested-docroot .");
  }

  private array $indices = [
    "server",
  ];

  private array $environments = ["qa", "dev", "test", "live"];

  private array $sites = ["server"];

  /**
   * Generates a cryptographically secure random string for the password.
   *
   * @param int $length
   *   Length of the random string. Default: 64.
   * @param string $keyspace
   *   The set of characters that can be part of the output string. Default:
   *   '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.
   *
   * @return string
   *   The random string.
   *
   * @throws \RangeException|\Exception
   */
  protected function randomStr(
    int $length = 64,
    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  ): string {
    if ($length < 1) {
      throw new RangeException("Length must be a positive integer");
    }
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
      $pieces[] = $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
  }

  /**
   * Provision command.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string|null $environment
   *   The environment ID. To test changes in the index config selectively.
   *
   * @throws \Exception
   */
  public function elasticsearchProvision(string $es_url, string $username, string $password, ?string $environment = NULL): void {
    $needs_users = TRUE;

    $es_url = rtrim($es_url, '/');
    if (strstr($es_url, '//elasticsearch:') !== FALSE) {
      // Detect DDEV.
      self::$indexPrefix = 'elasticsearch_index_db_';
      $needs_users = FALSE;
    }
    else {
      $result = json_decode($this
        ->taskExec("curl -u $username:$password $es_url/_security/user")
        ->printOutput(FALSE)
        ->run()
        ->getMessage(), TRUE);
      if (isset($result['error'])) {
        throw new Exception('Cannot connect to ES or security not enabled');
      }
      foreach (array_keys($result) as $existing_username) {
        foreach ($this->sites as $site) {
          if (strstr($existing_username, $site) !== FALSE) {
            // Users do exist with the site name.
            $needs_users = FALSE;
            break 2;
          }
        }

      }
    }

    $index_creation = $this->taskParallelExec();
    $role_creation = $this->taskParallelExec();
    $user_creation = $this->taskParallelExec();
    $credentials = [];
    if (!empty($environment)) {
      $this->environments = [$environment];
    }
    foreach ($this->environments as $environment) {
      foreach ($this->indices as $index) {
        $index_creation->process("curl -u $username:$password -X PUT $es_url/" . self::$indexPrefix . "{$index}_$environment");
      }
      foreach ($this->sites as $site) {
        if (!isset($credentials[$site])) {
          $credentials[$site] = [];
        }
        if (!isset($credentials[$site][$environment])) {
          $credentials[$site][$environment] = [];
        }
        $allowed_indices = [];
        foreach ($this->indices as $index) {
          if (strstr($index, $site) !== FALSE) {
            $allowed_indices[] = '"' . self::$indexPrefix . $index . '_' . $environment . '"';
          }
        }
        $allowed_indices = implode(',', $allowed_indices);

        $role_data = <<<END
{ "cluster": ["all"],
  "indices": [
    {
      "names": [ $allowed_indices ],
      "privileges": ["all"]
    }
  ]
}
END;

        $role_creation->process("curl -u $username:$password -X POST $es_url/_security/role/${site}_$environment -H 'Content-Type: application/json' --data '$role_data'");

        // Generate random password or re-use an existing one from the JSON.
        $existing_password = $this->getUserPassword($site, $environment);
        $user_pw = !empty($existing_password) ? $existing_password : $this->randomStr();
        $user_data = <<<END
{ "password" : "$user_pw",
  "roles": [ "{$site}_$environment" ]
}
END;
        $credentials[$site][$environment] = $user_pw;
        $user_creation->process("curl -u $username:$password -X POST $es_url/_security/user/{$site}_$environment -H 'Content-Type: application/json' --data '$user_data'");
      }

    }

    $index_creation->run();
    if ($needs_users) {
      $role_creation->run();
      $user_creation->run();

      // We expose the credentials as files on the system.
      // Should be securely handled and deleted after the execution.
      foreach ($credentials as $site => $credential_per_environment) {
        file_put_contents($site . '.es.secrets.json', json_encode($credential_per_environment));
      }
    }

    $this->elasticsearchAnalyzer($es_url, $username, $password);
  }

  /**
   * Apply / actualize the default analyzer.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user. Default: empty string.
   * @param string $password
   *   The password of the ES admin user. Default: empty string.
   *
   * @throws \Exception
   */
  public function elasticsearchAnalyzer(string $es_url, string $username = '', string $password = ''): void {
    $analyzer_data = <<<END
{
  "analysis": {
    "analyzer": {
      "default": {
        "type": "custom",
        "char_filter":  [ "html_strip" ],
        "tokenizer": "standard",
        "filter": [ "lowercase" ]
      }
    }
  }
}
END;

    $this->applyIndexSettings($es_url, $username, $password, $analyzer_data);
  }

  /**
   * Apply index configuration snippet to all indices.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string $data
   *   The JSON snippet to apply.
   */
  private function applyIndexSettings(string $es_url, string $username, string $password, string $data): void {
    foreach ($this->environments as $environment) {
      foreach ($this->indices as $index) {
        $this->taskExec("curl -u $username:$password -X POST $es_url/" . self::$indexPrefix . "{$index}_$environment/_close")->run();
        $this->taskExec("curl -u $username:$password -X PUT $es_url/" . self::$indexPrefix . "{$index}_$environment/_settings -H 'Content-Type: application/json' --data '$data'")->run();
        $this->taskExec("curl -u $username:$password -X POST $es_url/" . self::$indexPrefix . "{$index}_$environment/_open")->run();
      }
    }
  }

  /**
   * Returns an already existing password for the given user.
   *
   * @param string $site
   *   The site ID.
   * @param string $environment
   *   The environment ID.
   *
   * @return string|null
   */
  protected function getUserPassword(string $site, string $environment): ?string {
    $credentials_file = $site . '.es.secrets.json';
    if (!file_exists($credentials_file)) {
      return NULL;
    }
    $credentials = file_get_contents($credentials_file);
    if (empty($credentials)) {
      return NULL;
    }
    $credentials = json_decode($credentials, TRUE);
    if (!is_array($credentials)) {
      return NULL;
    }
    if (!isset($credentials[$environment])) {
      return NULL;
    }
    return $credentials[$environment];
  }

  /**
   * Generates log of changes since the given tag.
   *
   * @param string|null $tag
   *   The git tag to compare since. Usually the tag from the previous release.
   *   If you're releasing for example 1.0.2, then you should get changes since
   *   1.0.1, so $tag = 1.0.1. Omit for detecting the last tag automatically.
   *
   * @throws \Exception
   */
  public function generateReleaseNotes(?string $tag = NULL): void {
    $result = 0;
    // Check if the specified tag exists or not.
    if (!empty($tag)) {
      $result = $this->taskExec("git tag | grep \"$tag\"")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty($result)) {
        $this->say('The specified tag does not exist: ' . $tag);
      }
    }

    if (empty($result)) {
      $latest_tag = $this->taskExec("git tag --sort=version:refname | tail -n1")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      if (empty($latest_tag)) {
        throw new Exception('There are no tags in this repository.');
      }
      if ($this->confirm("Would you like to compare from the latest tag: $latest_tag?")) {
        $tag = $latest_tag;
      }
    }

    // Detect organization / repository name from git remote.
    $remote = $this->taskExec("git remote get-url origin")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    if (!empty($remote)) {
      $origin_parts = preg_split('/[:\/]/', str_replace('.git', '', $remote));
      if (!empty($origin_parts[1]) && !empty($origin_parts[2])) {
        $github_org = $origin_parts[1];
        $github_project = $origin_parts[2];
      }
    }

    if (!isset($github_org) || !isset($github_project)) {
      $this->say('No GitHub project or GitHub organization found, so not trying to fetch details from GitHub API.');
    }

    // This is the heart of the release notes, the git history, we get all the
    // merge commits since the specified last version and later on we parse
    // the output. Optionally we enrich it with metadata from GitHub REST API.
    $git_command = "git log --merges --pretty=format:'%s¬¬|¬¬%b'";
    if (!empty($tag)) {
      $git_command .= " $tag..";
    }
    $log = $this->taskExec($git_command)->printOutput(FALSE)->run()->getMessage();
    $lines = explode("\n", $log);

    $this->say('Copy release notes below');

    $this->printReleaseNotesTitle('Changelog');

    $pull_requests_per_issue = [];
    $no_issue_lines = [];
    $contributors = [];
    $issue_titles = [];
    $additions = 0;
    $deletions = 0;
    $changed_files = 0;

    foreach ($lines as $line) {
      $log_messages = explode("¬¬|¬¬", $line);
      $pr_matches = [];
      preg_match_all('/Merge pull request #([0-9]+)/', $line, $pr_matches);

      if (count($log_messages) < 2) {
        // No log message at all, not meaningful for changelog.
        continue;
      }

      if (!isset($pr_matches[1][0])) {
        // Could not detect PR number.
        continue;
      }

      $log_messages[1] = trim($log_messages[1]);
      if (empty($log_messages[1])) {
        // Whitespace-only log message, not meaningful for changelog.
        continue;
      }
      $pr_number = $pr_matches[1][0];
      if (!empty($github_org) && !empty($github_project)) {
        $pr_details = $this->githubApiGet("repos/$github_org/$github_project/pulls/$pr_number");
        if (!empty($pr_details->user)) {
          $contributors[] = '@' . $pr_details->user->login;
          $additions += $pr_details->additions;
          $deletions += $pr_details->deletions;
          $changed_files += $pr_details->changed_files;
        }
      }

      // The issue number is a required part of the branch name,
      // So usually we can grab it from the log too, but that's optional
      // If we cannot detect it, we still print a less verbose changelog line.
      $issue_matches = [];
      preg_match_all('!from [a-zA-Z-_0-9]+/([0-9]+)!', $line, $issue_matches);

      if (isset($issue_matches[1][0])) {
        $issue_number = $issue_matches[1][0];
        if (!isset($issue_titles[$issue_number]) && !empty($github_org) && !empty($github_project)) {
          $issue_details = $this->githubApiGet("repos/$github_org/$github_project/issues/$issue_number");
          if (!empty($issue_details->title)) {
            $issue_titles[$issue_number] = $issue_details->title;
            $contributors[] = '@' . $issue_details->user->login;
          }
        }

        if (isset($issue_titles[$issue_number])) {
          $issue_line = "- $issue_titles[$issue_number] (#$issue_number)";
        }
        else {
          $issue_line = "- Issue #$issue_number";
        }
        if (!isset($pull_requests_per_issue[$issue_line])) {
          $pull_requests_per_issue[$issue_line] = [];
        }
        $pull_requests_per_issue[$issue_line][] = "  - $log_messages[1] (#{$pr_matches[1][0]})";
      }
      else {
        $no_issue_lines[] = "- $log_messages[1] (#$pr_number)";
      }
    }

    foreach ($pull_requests_per_issue as $issue_line => $pr_lines) {
      print $issue_line . "\n";
      foreach ($pr_lines as $pr_line) {
        print $pr_line . "\n";
      }
    }

    $this->printReleaseNotesSection('', $no_issue_lines);

    if (isset($github_org)) {
      $contributors = array_count_values($contributors);
      arsort($contributors);
      $this->printReleaseNotesSection('Contributors', $contributors, TRUE);

      $this->printReleaseNotesSection('Code statistics', [
        "Lines added: $additions",
        "Lines deleted: $deletions",
        "Files changed: $changed_files",
      ]);
    }
  }

  /**
   * Print a section for the release notes.
   *
   * @param string $title
   *   Section title.
   * @param array $lines
   *   Bullet points.
   */
  protected function printReleaseNotesSection(string $title, array $lines, bool $print_key = FALSE): void {
    if (!empty($title)) {
      $this->printReleaseNotesTitle($title);
    }
    foreach ($lines as $key => $line) {
      if ($print_key) {
        print "- $key ($line)\n";
      }
      elseif (substr($line, 0, 1) == '-') {
        print "$line\n";
      }
      else {
        print "- $line\n";
      }
    }
  }

  /**
   * Print a title for the release notes.
   *
   * @param string $title
   *   Section title.
   */
  protected function printReleaseNotesTitle(string $title): void {
    echo "\n\n## $title\n";
  }

  /**
   * Performs a GET request towards GitHub API using personal access token.
   *
   * @param string $path
   *   Resource/path to GET.
   *
   * @return mixed|null
   *   Decoded response or NULL.
   *
   * @throws \Exception
   */
  protected function githubApiGet(string $path) {
    $token = getenv('GITHUB_ACCESS_TOKEN');
    $username = getenv('GITHUB_USERNAME');
    if (empty($token)) {
      throw new Exception('Specify the personal access token in GITHUB_ACCESS_TOKEN environment variable before invoking the release notes generator in order to be able to fetch details of issues and pull requests');
    }
    if (empty($username)) {
      throw new Exception('Specify the GitHub username in GITHUB_USERNAME environment variable before invoking the release notes generator in order to be able to fetch details of issues and pull requests');
    }
    // We might not have a sane Drupal instance, let's not rely on Drupal API
    // to generate release notes.
    $ch = curl_init('https://api.github.com/' . $path);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Drupal Starter Release Notes Generator');
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $token);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = empty($result) ? NULL : json_decode($result);
    if (substr((string) $http_code, 0, 1) != 2) {
      throw new Exception("Failed to request the API:\n" . print_r($result, TRUE));
    }
    return $result;
  }

  /**
   * Update the caniuse-lite browserslist db.
   *
   * Any changes made as a result of this command should be committed.
   *
   * @return \Robo\ResultData
   *   The result.
   */
  public function caniuseUpdatedb(): ResultData {
    return $this->_exec('cd ' . self::THEME_BASE . ' && npx browserslist@latest --update-db');
  }

}
