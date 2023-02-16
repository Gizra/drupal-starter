<?php

namespace RoboComponents;

use Symfony\Component\Yaml\Yaml;

/**
 * Deployment tailored to Pantheon.io.
 */
trait DeploymentTrait {

  /**
   * The wait time between deployment checks in microseconds.
   */
  public static int $deploymentWaitTime = 500000;

  /**
   * The GitHub project slug.
   *
   * @var string
   */
  public static string $githubProject = 'Gizra/drupal-starter';

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
    '.travis.yml',
    'ci-scripts',
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
    'node_modules',
  ];

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
      throw new \Exception('The working directory is dirty. Please commit or stash the pending changes.');
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
    catch (\Exception $e) {
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

    $result = $this
      ->taskExec('git status -s')
      ->printOutput(FALSE)
      ->run();

    if ($result->getMessage()) {
      $this->say($result->getMessage());
      throw new \Exception('The Pantheon directory is dirty. Please commit any pending changes.');
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
        $this->yell(strtr('This current commit @current-commit cannot be deployed, since new commits have been created since, so we don\'t want to deploy an older version. Result was: @result', [
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

    // Remove the dev dependencies before pushing up to Pantheon.
    $this->taskExec("composer install --no-dev")->run();

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', self::$deploySyncExcludes);

    // Copy all files and folders.
    $result = $this->_exec("rsync -az -q --delete $rsync_exclude_string . $pantheon_directory")->getExitCode();
    if ($result !== 0) {
      throw new \Exception('File sync failed');
    }

    // The settings.pantheon.php is managed by Pantheon, there can be updates,
    // site-specific modifications belong to settings.php.
    $this->_exec("cp web/sites/default/settings.pantheon.php $pantheon_directory/web/sites/default/settings.php");

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
    $result = $this->taskExec("cd $pantheon_directory && git pull && git add . && git commit -am $commit_message && git push")
      ->printOutput(FALSE)
      ->run();
    if (str_contains($result->getMessage(), 'nothing to commit, working tree clean')) {
      $this->say('Nothing to commit, working tree clean');
    }
    print $result->getMessage();

    if ($result->getExitCode() !== 0) {
      throw new \Exception('Pushing to the remote repository failed');
    }

    // Let's wait until the code is deployed to the environment.
    // This "git push" above is as async operation, so prevent invoking
    // for instance drush cim before the new changes are there.
    usleep(self::$deploymentWaitTime);
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_env = $branch_name == 'master' ? 'dev' : $branch_name;

    do {
      $code_sync_completed = $this->_exec("terminus workflow:list " . $pantheon_info['name'] . " --format=csv | grep " . $pantheon_env . " | grep Sync | awk -F',' '{print $5}' | grep running")->getExitCode();
      usleep(self::$deploymentWaitTime);
    } while (!$code_sync_completed);
    $this->deployPantheonSync($pantheon_env, FALSE);
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
    $yaml = Yaml::parseFile('./.ddev/providers/pantheon.yaml');
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
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new \Exception('The site could not be fully updated at Pantheon. Try "ddev robo deploy:pantheon-install-env" manually.');
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-r")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- sapi-i")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('The deployment went well, but the re-indexing to ElasticSearchTrait failed. Try to perform manually later.');
    }

    $result = $this->taskExecStack()
      ->stopOnFail()
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Could not generate a login link. Try again manually or check earlier errors.');
    }
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
   *
   * @throws \Exception
   */
  public function deployPantheonInstallEnv(string $env = 'qa', string $pantheon_name = NULL): void {
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

    $task
      ->exec("terminus remote:drush $pantheon_terminus_environment -- si server --no-interaction --existing-config")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- en server_migrate --no-interaction")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- migrate:import --group=server")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- pm:uninstall migrate -y")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- set-homepage")
      ->exec("terminus remote:drush $pantheon_terminus_environment -- uli");

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
   *   Personal GitHub token (Travis auth):
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
    $this->_exec("cp .travis.template.yml .travis.yml");
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
        throw new \Exception('The installation of the dependencies failed.');
      }
    }

    $result = $this->taskExec('ssh-keygen -f travis-key -P ""')->run();
    if ($result->getExitCode() !== 0) {
      throw new \Exception('The key generation failed.');
    }

    $result = $this->taskExec('travis login --pro --github-token="' . $github_token . '"')->run();
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
      throw new \Exception("git add failed.");
    }
    $this->say("The project was prepared for the automatic deployment to Pantheon");
    $this->say("Review the changes and make a commit from the added files.");
    $this->say("Add the SSH key to the Pantheon account: https://pantheon.io/docs/ssh-keys .");
    $this->say("Add the SSH key to the GitHub project as a deploy key: https://docs.github.com/en/developers/overview/managing-deploy-keys .");
    $this->say("Convert the project to nested docroot: https://pantheon.io/docs/nested-docroot .");
  }

  /**
   * Posts a comment on the GitHub issue that the code got deployed to Pantheon.
   *
   * @param string $pantheon_environment
   *   The Pantheon environment where the code was deployed.
   */
  public function deployNotify(string $pantheon_environment = 'qa') {
    $github_token = getenv('GITHUB_TOKEN');
    $git_commit_message = getenv('TRAVIS_COMMIT_MESSAGE');
    if (strstr($git_commit_message, 'Merge pull request') === FALSE && strstr($git_commit_message, ' (#') === FALSE) {
      $this->say($git_commit_message);
      return;
    }

    $issue_matches = [];
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
      // Retrieve the issue number from the PR description via GitHub API.
      $pr_number = $pr_matches[1][0];
      $pr = $this->taskExec("curl -H \"Authorization: token $github_token\" https://api.github.com/repos/" . self::$githubProject . "/pulls/$pr_number")
        ->printOutput(FALSE)
        ->run()
        ->getMessage();
      $pr = json_decode($pr);
      if (!isset($pr->body)) {
        $this->say("Could not determine the issue number from the PR: $git_commit_message");
        return;
      }
      // The issue number should be the first #1234 in the PR description.
      preg_match_all('!#([0-9]+)!', $pr->body, $issue_matches);
      if (!isset($issue_matches[1][0])) {
        $this->say("Could not determine the issue number from the PR description: $pr->body");
        return;
      }
      $issue_number = $issue_matches[1][0];

    }
    else {
      $issue_number = $issue_matches[1][0];
      if (empty($issue_number) || !is_numeric($issue_number)) {
        throw new \Exception("Could not determine the issue number from the branch name in the commit message: $git_commit_message");
      }
    }

    if (empty($issue_number)) {
      $this->say("Giving up, no notification sent to GitHub");
      return;
    }

    $pantheon_info = $this->getPantheonNameAndEnv();
    // Retrieve environment domain name.
    $domain = $this->taskExec("terminus env:info " . $pantheon_info['name'] . "." . $pantheon_environment . " --field=domain --format=list")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $this->say("Notifying GitHub of the deployment");
    if (empty($pr_number)) {
      $issue_comment = "{\"body\": \"The latest merged PR just got deployed successfully to Pantheon [`$pantheon_environment`](https://" . $domain . "/) environment\"}";
    }
    else {
      $issue_comment = "{\"body\": \"The latest merged PR #$pr_number just got deployed successfully to Pantheon [`$pantheon_environment`](https://" . $domain . "/) environment\"}";
    }
    $exit_code = $this->taskExec("curl -X POST -H 'Authorization: token $github_token' -d '$issue_comment' https://api.github.com/repos/" . self::$githubProject . "/issues/$issue_number/comments")
      ->printOutput(FALSE)
      ->run()
      ->getExitCode();
    if ($exit_code) {
      throw new \Exception("Could not notify GitHub of the deployment, GitHub API error.");
    }
  }

}
