<?php

use ScssPhp\ScssPhp\Compiler;
use Lurker\Event\FilesystemEvent;
use Robo\Tasks;
use Symfony\Component\EventDispatcher\Event;

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

}
