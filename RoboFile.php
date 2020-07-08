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
   *  List of directories.
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
   * @param string $branchName
   *   The branch name to commit to. Default to master.
   *
   * @throws \Exception
   */
  public function deployPantheon($branchName = 'master') {
    if (empty(self::PANTHEON_NAME)) {
      throw new Exception('You need to fill the "PANTHEON_NAME" const in the Robo file. so it will know what is the name of your site.');
    }

    $pantheonDirectory = '.pantheon';

    if (!file_exists($pantheonDirectory) || !is_dir($pantheonDirectory)) {
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
      ->taskExec("cd $pantheonDirectory && git status -s")
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new Exception('The Pantheon directory is dirty. Please commit any pending changes.');
    }

    // Validate pantheon.yml has web_docroot: true
    if (!file_exists($pantheonDirectory . '/pantheon.yml')) {
      throw new Exception("pantheon.yml is missing from the Pantheon directory ($pantheonDirectory)");
    }

    $yaml = Yaml::parseFile($pantheonDirectory . '/pantheon.yml');
    if (empty($yaml['web_docroot'])) {
      throw new Exception("'web_docroot: true' is missing from pantheon.yml in Pantheon directory ($pantheonDirectory)");
    }

    $this->_exec("cd $pantheonDirectory && git checkout $branchName");

    // Compile theme
    $this->themeCompile();

    $rsyncExclude = [
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

    $rsyncExcludeString = '--exclude=' . join(' --exclude=', $rsyncExclude);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsyncExcludeString . $pantheonDirectory")->getExitCode();
    if ($result !== 0) {
      throw new Exception('File sync failed');
    }

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
    // @todo: Ignore it from rsync, but './.gitignore' didn't work.
    $this->_exec("cd $pantheonDirectory && git checkout .gitignore");

    $this->_exec("cd $pantheonDirectory && git status");

    $commitAndDeployConfirm = $this->confirm('Commit changes and deploy?', TRUE);
    if (!$commitAndDeployConfirm) {
      $this->say('Aborted commit and deploy, you can do it manually');

      // The Pantheon repo is dirty, so check if we want to clean it up before
      // exit.
      $cleanupPantheonDirectoryConfirm = $this->confirm("Revert any changes on $pantheonDirectory directory (i.e. `git checkout .`)?");
      if (!$cleanupPantheonDirectoryConfirm) {
        // Keep folder as is.
        return;
      }

      // We repeat "git clean" twice, as sometimes it seems that a single one
      // doesn't remove all directories.
      $this->_exec("cd $pantheonDirectory && git checkout . && git clean -fd && git clean -fd && git status");

      return;
    }

    $pantheonName = self::PANTHEON_NAME;
    $pantheonTerminusEnvironment = $pantheonName . '.dev';

    $result = $this->_exec("cd $pantheonDirectory && git pull && git add . && git commit -am 'Site update' && git push")->getExitCode();
    if ($result !== 0) {
      throw new Exception('Pushing to the remote repository failed');
    }
    $this->deployPantheonSync('dev', false);
  }

  /**
   * Deploy site from one env to the other on Pantheon.
   *
   * @param string $env
   *   The environment to update.
   * @param bool $doDeploy
   *   Determine if a deploy should be done by terminus. That is, for example
   *   should TEST environment be updated from DEV.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function deployPantheonSync(string $env = 'test', bool $doDeploy = true) {
    $pantheonName = self::PANTHEON_NAME;
    $pantheonTerminusEnvironment = $pantheonName . '.' . $env;

    $task = $this->taskExecStack()
      ->stopOnFail();

    if ($doDeploy) {
      $task->exec("terminus env:deploy $pantheonTerminusEnvironment");
    }

    $result = $task
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cr")

      // A second cache-clear, because Drupal...
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cr")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- updb -y")

      // A second config import, because Drupal...
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cim -y")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cim -y")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- uli")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new Exception('The site could not be fully updated at Pantheon.');
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
      'profiles/custom'
    ];

    $errorCode = null;

    foreach ($directories as $directory) {
      foreach ($standards as $standard) {
        $arguments = "--standard=$standard -p --ignore=server_theme/dist,node_modules --colors --extensions=php,module,inc,install,test,profile,theme,js,css,yaml,txt,md";

        foreach ($commands as $command) {
          $result = $this->_exec("cd web && ../vendor/bin/$command $directory $arguments");
          if (empty($errorCode) && !$result->wasSuccessful()) {
            $errorCode = $result->getExitCode();
          }
        }
      }
    }

    if (!empty($errorCode)) {
      return new Robo\ResultData($errorCode, 'PHPCS found some issues');
    }
  }

  /**
   * Prepares the repository to perform automatic deployment to Pantheon.
   *
   * @param string $token
   *   Terminus machine token: https://pantheon.io/docs/machine-tokens
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
  }

}
