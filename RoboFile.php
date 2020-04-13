<?php

use ScssPhp\ScssPhp\Compiler;
use Lurker\Event\FilesystemEvent;
use Robo\Tasks;
use Symfony\Component\EventDispatcher\Event;

/**
 * Robo commmands.
 */
class RoboFile extends Tasks {

  const OPTIMIZED_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Crunched';

  const DEV_FORMATTER = 'ScssPhp\ScssPhp\Formatter\Expanded';

  const THEME_BASE = 'web/themes/custom/theme_server';

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
      $this->_exec("rm -rf $directory");
      $this->_exec("mkdir -p $directory");
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

    return;

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

}
