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

  const THEME_BASE = 'web/themes/custom/server_theme';

  /**
   * The wait time between deployment checks in microseconds.
   */
  const DEPLOYMENT_WAIT_TIME = '500000';

  private static $indexPrefix = 'elasticsearch_index_pantheon_';

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
    // Compress all SVGs.
    $this->themeCompressSvg();

    // Notice we don't cleanup the `dist/css` as we'd want parcel, which
    // bundles TailWind and Sass to keep using its cache - for faster builds.
    // We also don't deal with the "fonts" directory, as Parcel already copies
    // the fonts as part of compiling the css file.
    $directories = [
      'js',
      'images',
    ];

    // Delete Parcel's cache directory. Caching will occur while robo is
    // running, but otherwise we remove it, since seems caching is sometimes too
    // aggressive.
    $this->_deleteDir(self::THEME_BASE . '/.cache');

    // Cleanup and create directories.
    $this->_deleteDir(self::THEME_BASE . '/dist');
    foreach ($directories as $dir) {
      $directory = self::THEME_BASE . '/dist/' . $dir;
      $this->_mkdir($directory);
    }

    $theme_dir = self::THEME_BASE;

    // Make sure we have all the node packages.
    $this->_exec("cd $theme_dir && npm install");

    // If we are asked to optimize, we make sure to purge tailwind's css, by
    // passing the `PURGE_TAILWIND` env variable.
    // @see tailwind.config.js.
    $purge_prefix = $optimize ? 'PURGE_TAILWIND=1' : '';
    $minify = $optimize ? '' : '--no-minify';
    $result = $this->_exec("cd $theme_dir && $purge_prefix npx parcel build -d ./dist/css ./src/scss/style.scss $minify --out-dir=./dist/css --out-file=style.css --public-url ./");

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

    $this->_exec('drush cache:rebuild');
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
      'server.es.secrets.json',
    ];

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', $rsync_exclude);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsync_exclude_string . $pantheon_directory")->getExitCode();
    if ($result !== 0) {
      throw new Exception('File sync failed');
    }

    // We don't want to change Pantheon's git ignore, as we do want to commit
    // vendor and contrib directories.
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
      $code_sync_completed = $this->_exec("terminus workflow:list " . self::PANTHEON_NAME . " --format=csv | grep " . $pantheon_env . " | grep Sync | awk -F',' '{print $5}' | grep running")->getExitCode();
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
   * Compress SVG files in specific directories.
   *
   * This function is being called as part of `theme:compile`.
   * @see compileTheme_()
   */
  public function themeCompressSvg() {
    $directories = [
      './src/images/*.svg',
    ];

    $error_code = NULL;

    foreach ($directories as $directory) {
      $result = $this->_exec("cd web/themes/custom/server_theme && ./node_modules/svgo/bin/svgo $directory");
      if (empty($error_code) && !$result->wasSuccessful()) {
        $error_code = $result->getExitCode();
      }
    }

    if (!empty($error_code)) {
      return new Robo\ResultData($error_code, '`svgo` failed to run.');
    }
  }

  /**
   * Install the site on specific env on Pantheon from scratch.
   *
   * Running this command via `ddev` will require terminus login inside ddev:
   * `ddev auth ssh`
   *
   * @param string $env
   *   The environment to install (default='qa').
   *
   * @throws \Robo\Exception\TaskException
   */
  public function deployPantheonInstallEnv(string $env = 'qa') {
    $forbidden_envs = [
      'live',
    ];
    if (in_array($env, $forbidden_envs)) {
      throw new Exception("Reinstalling the site on `{$env}` environment is forbidden.");
    }

    $pantheon_name = self::PANTHEON_NAME;
    $pantheon_terminus_environment = $pantheon_name . '.' . $env;

    // This set of commands should work, so expecting no failures.
    // (tend to invoke the same flow as DDEV's `config.local.yaml`.
    $task = $this
      ->taskExecStack()
      ->stopOnFail();

    $result = $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- si server -y --existing-config")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- en server_migrate -y")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- migrate:import --group=server")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm:uninstall server_migrate")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli")
      ->run()
      ->getExitCode();

    // For these environments, set the `admin` user's password to `1234`.
    $envs_to_set_admin_simple_password = [
      'qa'
    ];
    if (in_array($env, $envs_to_set_admin_simple_password)) {
      $task->exec("terminus remote:drush $pantheon_terminus_environment -- user:password admin 1234")->run();
    }

    if ($result !== 0) {
      throw new Exception("The site failed to install on Pantheon's `{$env}` environment.");
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


  private $indices = [
    "server",
  ];

  private $environments = ["qa", "dev", "test", "live"];

  private $sites = ["server"];

  /**
   * Generates a cryptographically secure random string for the password.
   *
   * @param int $length
   *   Length of the random string.
   * @param string $keyspace
   *   The set of characters that can be part of the output string.
   *
   * @return string
   *   The random string.
   *
   * @throws \Exception
   */
  protected function randomStr(
    int $length = 64,
    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  ): string {
    if ($length < 1) {
      throw new \RangeException("Length must be a positive integer");
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
   *
   * @throws \Exception
   */
  public function elasticsearchProvision($es_url, $username, $password) {
    $es_url = rtrim($es_url, '/');
    if (strstr($es_url, '//elasticsearch:') !== FALSE) {
      // Detect DDEV.
      self::$indexPrefix = 'elasticsearch_index_db_';
    }
    $index_creation = $this->taskParallelExec();
    $role_creation = $this->taskParallelExec();
    $user_creation = $this->taskParallelExec();
    $credentials = [];
    foreach ($this->environments as $environment) {
      foreach ($this->indices as $index) {
        $index_creation->process("curl -u {$username}:{$password} -X PUT {$es_url}/" . self::$indexPrefix . "{$index}_{$environment}");
      }
      $all_roles = [];
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

        $role_creation->process("curl -u {$username}:{$password} -X POST {$es_url}/_security/role/${site}_${environment} -H 'Content-Type: application/json' --data '$role_data'");
        $all_roles[] = '"' . $site . '_' . $environment . '"';

        // Generate random password or re-use an existing one from the JSON.
        $existing_password = $this->getUserPassword($site, $environment);
        $user_pw = !empty($existing_password) ? $existing_password : $this->randomStr();
        $user_data = <<<END
{ "password" : "$user_pw",
  "roles": [ "{$site}_{$environment}" ]
}
END;
        $credentials[$site][$environment] = $user_pw;
        $user_creation->process("curl -u {$username}:{$password} -X POST {$es_url}/_security/user/${site}_${environment} -H 'Content-Type: application/json' --data '$user_data'");
      }

    }

    $index_creation->run();
    $role_creation->run();
    $user_creation->run();

    $this->elasticsearchSynonyms($es_url, $username, $password);
    $this->elasticsearchStopwords($es_url, $username, $password);
    $this->elasticsearchAnalyzer($es_url, $username, $password);

    // We expose the credentials as files on the system.
    // Should be securely handled and deleted after the execution.
    foreach ($credentials as $site => $credential_per_environment) {
      file_put_contents($site . '.es.secrets.json', json_encode($credential_per_environment));
    }
  }

  /**
   * Apply / actualize stopwords.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string $english_stopword_path
   *   The path to the English stopword list.
   *
   * @throws \Exception
   */
  public function elasticsearchStopwords($es_url, $username = '', $password = '', $english_stopword_path = 'config/elasticsearch/stopwords.txt') {
    if (!file_exists($english_stopword_path)) {
      throw new Exception("The English stopword file does not exist");
    }

    $stopword_combined = file($english_stopword_path);
    if (empty($stopword_combined)) {
      throw new Exception("The stopword lists would be empty, check the specified files");
    }
    $prepared_stopwords = [];
    foreach ($stopword_combined as $stopword) {
      $prepared_stopwords[] = '"' . trim($stopword) . '"';
    }
    $stopword_list = implode(',', $prepared_stopwords);
    $stopword_data = <<<END
{
  "analysis": {
    "filter": {
      "stop": {
        "type": "stop",
        "stopwords": [ $stopword_list ]
      }
    }
  }
}
END;

    $this->applyIndexSettings($es_url, $username, $password, $stopword_data);
  }

  /**
   * Apply / actualize synonyms.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string $english_synonym_path
   *   The path to the English synonym list.
   *
   * @throws \Exception
   */
  public function elasticsearchSynonyms($es_url, $username = '', $password = '', $english_synonym_path = 'config/elasticsearch/synonyms.txt') {
    if (!file_exists($english_synonym_path)) {
      throw new Exception("The English synonym file does not exist");
    }
    $synonym_combined = file($english_synonym_path);
    if (empty($synonym_combined)) {
      throw new Exception("The synonym lists would be empty, check the specified files");
    }
    $prepared_synonyms = [];
    foreach ($synonym_combined as $synonym) {
      $prepared_synonyms[] = '"' . trim($synonym) . '"';
    }
    $synonym_list = implode(',', $prepared_synonyms);
    $synonym_data = <<<END
{
  "analysis": {
    "filter": {
      "synonym": {
        "type": "synonym_graph",
        "lenient": true,
        "synonyms": [ $synonym_list ]
      }
    }
  }
}
END;

    $this->applyIndexSettings($es_url, $username, $password, $synonym_data);
  }

  /**
   * Apply / actualize synonyms.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   *
   * @throws \Exception
   */
  public function elasticsearchAnalyzer($es_url, $username = '', $password = '') {
    $analyzer_data = <<<END
{
  "analysis": {
    "analyzer": {
      "default": {
        "type": "custom",
        "char_filter":  [ "html_strip" ],
        "tokenizer": "standard",
        "filter": [ "lowercase", "stop", "synonym" ]
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
  private function applyIndexSettings($es_url, $username, $password, $data) {
    foreach ($this->environments as $environment) {
      foreach ($this->indices as $index) {
        $this->taskExec("curl -u {$username}:{$password} -X POST {$es_url}/" . self::$indexPrefix . "{$index}_{$environment}/_close")->run();
        $this->taskExec("curl -u {$username}:{$password} -X PUT {$es_url}/" . self::$indexPrefix . "{$index}_{$environment}/_settings -H 'Content-Type: application/json' --data '$data'")->run();;
        $this->taskExec("curl -u {$username}:{$password} -X POST {$es_url}/" . self::$indexPrefix . "{$index}_{$environment}/_open")->run();
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
   * @return string|NULL
   */
  protected function getUserPassword($site, $environment) {
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
  public function generateReleaseNotes($tag = NULL) {
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
      if (!$this->confirm("Would you like to compare from the latest tag: $latest_tag?")) {
        $this->say("Specify the tag as an argument");
        exit(1);
      }
      $tag = $latest_tag;
    }

    $log = $this->taskExec("git log --merges --pretty=format:'%s¬¬|¬¬%b' $tag..")->printOutput(FALSE)->run()->getMessage();
    $lines = explode("\n", $log);

    $this->say('Copy release notes below');
    echo "Changelog:\n";

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

      // The issue number is a required part of the branch name
      // So usually we can grab it from the log too, but that's optional
      // If we cannot detect it, we still print a less verbose changelog line.
      $issue_matches = [];
      preg_match_all('!from Gizra/([0-9]+)!', $line, $issue_matches);

      if (isset($issue_matches[1][0])) {
        print "- Issue #{$issue_matches[1][0]} :{$log_messages[1]} (#{$pr_matches[1][0]})\n";
      }
      else {
        print "- {$log_messages[1]} (#{$pr_matches[1][0]})\n";
      }
    }
  }

}
