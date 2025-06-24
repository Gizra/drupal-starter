<?php

namespace RoboComponents;

use Lurker\Event\FilesystemEvent;
use Robo\ResultData;
use Symfony\Component\Finder\Finder;

/**
 * Compilation of theme source assets.
 */
trait ThemeTrait {

  /**
   * The name of the theme.
   *
   * @var string
   */
  public static string $themeName = 'server_theme';

  /**
   * The base path of the theme.
   *
   * @var string
   */
  public static string $themeBase = 'web/themes/custom/server_theme';

  /**
   * Compile the theme.
   *
   * @param bool $optimize
   *   Indicate whether to optimize during compilation.
   */
  private function doThemeCompile(bool $optimize = FALSE): void {
    $directories = [
      'js',
      'images',
    ];

    // Cleanup and create directories.
    $this->_deleteDir(self::$themeBase . '/dist');
    foreach ($directories as $dir) {
      $directory = self::$themeBase . '/dist/' . $dir;
      $this->_mkdir($directory);
    }

    $theme_dir = self::$themeBase;

    // Make sure we have all the node packages.
    $this->_exec("cd $theme_dir && npm install");

    $result = $this->_exec("cd $theme_dir && npx postcss ./src/pcss/style.pcss --output=./dist/css/style.css");

    // Safety check to verify theme was properly compiled before deployment.
    if (!file_exists(sprintf('%s/dist/css/style.css', self::$themeBase))) {
      throw new \Exception('Theme compilation failed.');
    }

    if ($result->getExitCode() !== 0) {
      $this->taskCleanDir(['dist/css']);
      return;
    }

    // Javascript.
    if ($optimize) {
      // Minify the JS files.
      foreach (glob(self::$themeBase . '/src/js/*.js') as $js_file) {
        // Make the path relative to the theme root.
        $from = str_replace('web/themes/custom/server_theme/', '', $js_file);
        $to = str_replace('src/', 'dist/', $from);
        // Minify the js.
        $this->_exec("cd $theme_dir && npx minify $from > $to");
      }
    }
    else {
      $this->_copyDir(self::$themeBase . '/src/js', self::$themeBase . '/dist/js');
    }

    // Fonts.
    $this->_copyDir(self::$themeBase . '/src/fonts', self::$themeBase . '/dist/fonts');

    // Images - Copy everything first.
    $this->_copyDir(self::$themeBase . '/src/images', self::$themeBase . '/dist/images');

    // Then for the formats that we can optimize, perform it.
    if ($optimize) {
      $input = [
        self::$themeBase . '/src/images/*.jpg',
        self::$themeBase . '/src/images/*.png',
      ];

      $this->taskImageMinify($input)
        ->to(self::$themeBase . '/dist/images/')
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
        ->in(self::$themeBase . '/' . $directory)
        ->files()
        ->name('*.svg');

      if (!$finder->hasResults()) {
        // No SVG files.
        continue;
      }

      $result = $this->_exec("cd " . self::$themeBase . " && npx svgo $directory/*.svg");
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
      self::$themeBase . '/src',
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
   * Update the caniuse-lite browserslist db.
   *
   * Any changes made as a result of this command should be committed.
   *
   * @return \Robo\ResultData
   *   The result.
   */
  public function caniuseUpdatedb(): ResultData {
    return $this->_exec('cd ' . self::$themeBase . ' && npx browserslist@latest --update-db');
  }

}
