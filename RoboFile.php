<?php

use ScssPhp\ScssPhp\Compiler;
use Lurker\Event\FilesystemEvent;
use Robo\Tasks;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Yaml\Yaml;

/**
 * Robo commmands.
 */
class RoboFile extends Tasks {

  const OPTIMIZED_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Crunched';

  const DEV_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Expanded';

  const THEME_BASE = 'web/themes/custom/server_theme';

  /**
   * The wait time between deployment checks in microseconds.
   */
  const DEPLOYMENT_WAIT_TIME = '500000';

  /**
   * The Pantheon name.
   *
   * You need to fill this information for Robo to know what's the name of your
   * site.
   */
  const PANTHEON_NAME = '';

  /**
   * Compile the app; On success ...
   *
   * @param bool $optimize
   *   Indicate whether to optimize during compilation.
   */
  private function compileTheme_($optimize = FALSE) {
    // Stylesheets.
    $formatter = self::DEV_FORMATTER;
    if ($optimize) {
      $formatter = self::OPTIMIZED_FORMATTER;
    }

    $directories = [
      'css',
      'js',
      'images',
    ];

    // Cleanup directories.
    foreach ($directories as $dir) {
      $directory = self::THEME_BASE . '/dist/' . $dir;
      $this->taskCleanDir($directory);
      $this->_mkdir($directory);
    }

    $compiler_options = [];
    if (!$optimize) {
      $compiler_options['sourceMap'] = Compiler::SOURCE_MAP_INLINE;
    }

    // CSS.
    $result = $this->taskScss([
      self::THEME_BASE . '/src/scss/style.scss' => self::THEME_BASE . '/dist/css/style.css',
    ])
      ->setFormatter($formatter)
      ->importDir([self::THEME_BASE . '/src/scss'])
      ->compiler('scssphp', $compiler_options)
      ->run();

    if ($result->getExitCode() !== 0) {
      $this->taskCleanDir(['dist/css']);
      return $result;
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
    }
  }

  /**
   * Compile the theme (optimized).
   */
  public function themeCompile() {
    $this->say('Compiling (optimized).');
    $this->compileTheme_(TRUE);
  }

  /**
   * Compile the theme.
   *
   * Non-optimized.
   */
  public function themeCompileDebug() {
    $this->say('Compiling (non-optimized).');
    $this->compileTheme_();
  }

  /**
   * Directories that should be watched for the theme.
   *
   * @return array
   *   List of directories.
   */
  protected function monitoredThemeDirectories() {
    return [
      self::THEME_BASE . '/src',
    ];
  }

  /**
   * Watch the theme and compile on change (optimized).
   */
  public function themeWatch() {
    $this->say('Compiling and watching (optimized).');
    $this->compileTheme_(TRUE);
    foreach ($this->monitoredThemeDirectories() as $directory) {
      $this->taskWatch()
        ->monitor(
          $directory,
          function (Event $event) {
            $this->compileTheme_(TRUE);
          },
          FilesystemEvent::ALL
        )->run();
    }
  }

  /**
   * Watch the theme path and compile on change (non-optimized).
   */
  public function themeWatchDebug() {
    $this->say('Compiling and watching (non-optimized).');
    $this->compileTheme_();
    foreach ($this->monitoredThemeDirectories() as $directory) {
      $this->taskWatch()
        ->monitor(
          $directory,
          function (Event $event) {
            $this->compileTheme_();
          },
          FilesystemEvent::ALL
        )->run();
    }
  }

  /**
   * Deploy to Pantheon.
   *
   * @param string $branch_name
   *   The branch name to commit to. Default to master.
   *
   * @throws \Exception
   */
  public function deployPantheon($branch_name = 'master') {
    if (empty(self::PANTHEON_NAME)) {
      throw new Exception('You need to fill the "PANTHEON_NAME" const in the Robo file. so it will know what is the name of your site.');
    }

    $pantheon_directory = '.pantheon';

    if (!file_exists($pantheon_directory) || !is_dir($pantheon_directory)) {
      throw new Exception('Clone the Pantheon artifact repository first into the .pantheon directory');
    }

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new Exception('The working directory is dirty. Please commit the pending changes.');
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

    // Compile theme.
    $this->themeCompile();

    $rsync_exclude = [
      '.git',
      '.ddev',
      '.idea',
      '.pantheon',
      'sites/default',
      'pantheon.yml',
      'pantheon.upstream.yml',
      'travis-key.enc',
      'travis-key',
    ];

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', $rsync_exclude);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsync_exclude_string . $pantheon_directory")->getExitCode();
    if ($result !== 0) {
      throw new Exception('File sync failed');
    }

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
    // @todo: Ignore it from rsync, but './.gitignore' didn't work.
    $this->_exec("cd $pantheon_directory && git checkout .gitignore");

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

    $result = $this->_exec("cd $pantheon_directory && git pull && git add . && git commit -am 'Site update' && git push")->getExitCode();
    if ($result !== 0) {
      throw new Exception('Pushing to the remote repository failed');
    }

    // Let's wait until the code is deployed to the environment.
    // This "git push" above is as async operation, so prevent invoking
    // for instance drush cim before the new changes are there.
    usleep(self::DEPLOYMENT_WAIT_TIME);
    $pantheon_env = $branch_name == 'master' ? 'dev' : $branch_name;
    do {
      $code_sync_completed = $this->_exec("terminus workflow:list " . self::PANTHEON_NAME . " --format=csv | grep " . $pantheon_env . " | grep Sync | grep -v succeeded")->getExitCode();
      usleep(self::DEPLOYMENT_WAIT_TIME);
    } while (!$code_sync_completed);
    $this->deployPantheonSync($pantheon_env, FALSE);
  }

  /**
   * Deploy site from one env to the other on Pantheon.
   *
   * @param string $env
   *   The environment to update.
   * @param bool $do_deploy
   *   Determine if a deploy should be done by terminus. That is, for example
   *   should TEST environment be updated from DEV.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function deployPantheonSync(string $env = 'test', bool $do_deploy = TRUE) {
    $pantheon_name = self::PANTHEON_NAME;
    $pantheon_terminus_environment = $pantheon_name . '.' . $env;

    $task = $this->taskExecStack()
      ->stopOnFail();

    if ($do_deploy) {
      $task->exec("terminus env:deploy $pantheon_terminus_environment");
    }

    $result = $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")

      // A second cache-clear, because Drupal...
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cr")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- updb -y")

      // A second config import, because Drupal...
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim -y")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- cim -y")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new Exception('The site could not be fully updated at Pantheon. Try "ddev robo deploy:pantheon-reboot" manually.');
    }
  }

  /**
   * Perform a Code sniffer test, and fix when applicable.
   */
  public function phpcs() {
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
        $arguments = "--standard=$standard -p --ignore=server_theme/dist,node_modules --colors --extensions=php,module,inc,install,test,profile,theme,js,css,yaml,txt,md";

        foreach ($commands as $command) {
          $result = $this->_exec("cd web && ../vendor/bin/$command $directory $arguments");
          if (empty($error_code) && !$result->wasSuccessful()) {
            $error_code = $result->getExitCode();
          }
        }
      }
    }

    if (!empty($error_code)) {
      return new Robo\ResultData($error_code, 'PHPCS found some issues');
    }
  }

  /**
   * Prepares the repository to perform automatic deployment to Pantheon.
   *
   * @param string $token
   *   Terminus machine token: https://pantheon.io/docs/machine-tokens.
   * @param string $project_name
   *   The project machine name on Pantheon, for example: drupal-starter.
   * @param string $github_deploy_branch
   *   The branch that should be pushed automatically to Pantheon.
   * @param string $pantheon_deploy_branch
   *   The branch at the artifact repo that should be the target of the deploy.
   */
  public function deployConfigAutodeploy(string $token, string $project_name, $github_deploy_branch = 'master', string $pantheon_deploy_branch = 'master') {
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
        throw new \Exception('The installation of the dependencies failed.');
      }
    }

    $result = $this->taskExec('ssh-keygen -f travis-key -P ""')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The key generation failed.');
    }

    $result = $this->taskExec('travis login --pro')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The authentication with GitHub via Travis CLI failed.');
    }

    $result = $this->taskExec('travis encrypt-file travis-key --add --no-interactive --pro')
      ->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The encryption of the private key failed.');
    }

    $result = $this->taskExec('travis encrypt TERMINUS_TOKEN="' . $token . '" --add --no-interactive --pro')
      ->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The encryption of the Terminus token failed.');
    }

    $result = $this->taskExec("terminus connection:info {$project_name}.dev --fields='Git Command' --format=string | awk '{print $3}'")
      ->printOutput(FALSE)
      ->run();
    $pantheon_git_url = trim($result->getMessage());
    $host_parts = parse_url($pantheon_git_url);
    $pantheon_git_host = $host_parts['host'];
    $this->taskReplaceInFile('.travis.yml')
      ->from('{{ PANTHEON_GIT_URL }}')
      ->to($pantheon_git_url)
      ->run();
    $this->taskReplaceInFile('.travis.yml')
      ->from('{{ PANTHEON_GIT_HOST }}')
      ->to($pantheon_git_host)
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
      throw new \Exception("git add failed.");
    }
    $this->say("The project was prepared for the automatic deployment to Pantheon");
    $this->say("Review the changes and make a commit from the added files.");
    $this->say("Add the SSH key to the Pantheon account: https://pantheon.io/docs/ssh-keys .");
    $this->say("Add the SSH key to the GitHub project as a deploy key: https://docs.github.com/en/developers/overview/managing-deploy-keys .");
    $this->say("Convert the project to nested docroot: https://pantheon.io/docs/nested-docroot .");
  }

}
