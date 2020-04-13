<?php

use ScssPhp\ScssPhp\Compiler;
use Lurker\Event\FilesystemEvent;
use Robo\Tasks;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Yaml\Yaml;

/**
 * Theme compilation Robo file.
 */
class RoboFile extends Tasks {

  const OPTIMIZED_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Crunched';

  const DEV_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Expanded';

  const THEME_BASE = 'web/themes/custom/theme_server';

  const CSS_DIR = self::THEME_BASE . '/dist/css';

  const CSS_DIR_INIT_CMD = 'mkdir -p ' . self::CSS_DIR;

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

    if (!is_dir(self::CSS_DIR)) {
      $this->_exec(self::CSS_DIR_INIT_CMD);
    }

    $compiler_options = [];
    if (!$optimize) {
      $compiler_options['sourceMap'] = Compiler::SOURCE_MAP_INLINE;
    }

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

    // Images.
    // Copy everything first.
    $this->_copyDir(self::THEME_BASE . '/src/images', self::THEME_BASE . '/dist/images');

    if ($optimize) {
      // Then for the formats where we can optimize, perform it.
      $this->taskImageMinify(self::THEME_BASE . '/src/images/*.jpg')
        ->to(self::THEME_BASE . '/dist/images/')
        ->run();
      $this->taskImageMinify(self::THEME_BASE . '/src/images/*.png')
        ->to(self::THEME_BASE . '/dist/images/')
        ->run();
    }
  }

  /**
   * Compile the theme (optimized).
   */
  public function compileTheme() {
    $this->say('Compiling (optimized).');
    $this->compileTheme_(TRUE);
  }

  /**
   * Compile the theme.
   *
   * Non-optimized.
   */
  public function compileThemeDebug() {
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
  public function watchTheme() {
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
   * Watch the theme path and compile on change.
   *
   * Non-optimized, for `Debug.toString`.
   */
  public function watchThemeDebug() {
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

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      throw new Exception('The working directory is dirty. Please commit any pending changes.');
    }

    $result = $this
      ->taskExec("cd $pantheonDirectory && git status -s")
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
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
    $this->compileTheme();

    $rsyncExclude = [
      '.git',
      '.ddev',
      '.idea',
      '.pantheon',
      'sites/default',
      'pantheon.yml',
      'pantheon.upstream.yml',
    ];

    $rsyncExcludeString = '--exclude=' . join(' --exclude=', $rsyncExclude);

    // Copy all files and folders.
    $this->_exec("rsync -az --progress --delete $rsyncExcludeString . $pantheonDirectory");

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
    // @todo: Ignore it from rsync, but './.gitignore' didn't work.
    $this->_exec("cd $pantheonDirectory && git checkout .gitignore");

    $this->_exec("cd $pantheonDirectory && git status");

    $commitAndDeployConfirm = $this->confirm('Commit changes and deploy?');
    if (!$commitAndDeployConfirm) {
      $this->say('Aborted commit and deploy, you can do it manually');
      return;
    }

    $pantheonName = self::PANTHEON_NAME;
    $pantheonTerminusEnvironment = $pantheonName . '.dev';

    $this
      ->taskExecStack()
      ->exec("cd $pantheonDirectory && git pull && git add . && git commit -am 'Site update' && git push")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cr")

      // A second cache-clear, because Drupal...
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cr")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- updb -y")

      // A second config import, because Drupal...
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cim -y")
      ->exec("terminus remote:drush $pantheonTerminusEnvironment -- cim -y")
      ->run();
  }

}
