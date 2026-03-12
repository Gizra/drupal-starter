<?php

namespace RoboComponents;

use Robo\ResultData;

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
   */
  public function themeCompile(): void {
    $theme_dir = self::$themeBase;
    $directories = [
      'css',
      'fonts',
      'js',
      'images',
    ];

    // Cleanup and create directories.
    $this->_deleteDir(self::$themeBase . '/dist');
    foreach ($directories as $dir) {
      $directory = self::$themeBase . '/dist/' . $dir;
      $this->_mkdir($directory);
    }

    // Make sure we have all the node packages.
    $this->_exec("cd $theme_dir && npm install");

    // Compile all assets (CSS, JS, fonts, images) in parallel via npm scripts.
    $result = $this->_exec("cd $theme_dir && npm run build");

    // Safety check to verify CSS was properly compiled before deployment.
    if (!file_exists(sprintf('%s/dist/css/style.css', self::$themeBase))) {
      throw new \Exception('Theme compilation failed.');
    }

    if ($result->getExitCode() !== 0) {
      $this->taskCleanDir(['dist/css']);
      return;
    }

    $this->_exec('drush cache:rebuild');
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
