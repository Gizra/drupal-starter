<?php

namespace RoboComponents;

use Robo\Symfony\ConsoleIO;

/**
 * Automated way to launch a new client project from Drupal Starter.
 */
trait BootstrapTrait {

  /**
   * Bootstrap a new client project on Pantheon.io.
   *
   * @param string $project_name
   *   The Pantheon project name.
   * @param string $github_repository_url
   *   The clone URL of the GitHub repository.
   * @param string $terminus_token
   *   The Pantheon machine token.
   * @param string $github_token
   *   The GitHub personal access token for a user with access to this project.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user. Optional. If set, all the Pantheon environments
   *   will be protected with HTTP basic auth.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password. Optional.
   */
  public function bootstrapProject(string $project_name, string $github_repository_url, string $terminus_token, string $github_token, string $http_basic_auth_user = '', string $http_basic_auth_password = '') {
    // Extract organization and repo name from GitHub URL.
    preg_match('/github.com[:\/](.*)\/(.*)\.git/', $github_repository_url, $matches);
    $github_organization = $matches[1];
    $github_repo_name = $matches[2];

    $this->verifyRequirements($project_name, $github_organization, $github_repo_name, $terminus_token, $github_token, $http_basic_auth_user, $http_basic_auth_password);

    $this->prepareGithubRepository($project_name, $github_organization, $github_repo_name, $github_repository_url);

    $this->createPantheonProject($terminus_token, $project_name);

    $this->deployPantheonInstallEnv('dev', $project_name);
    $this->deployPantheonInstallEnv('qa', $project_name);

    $this->lockPantheonEnvironments($project_name, $http_basic_auth_user, $http_basic_auth_password);

    $tfa_secret = $this->taskExec("openssl rand -base64 32")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $this->taskExec('terminus self:plugin:install pantheon-systems/terminus-secrets-plugin')->run();
    $this->taskExec("terminus secrets:set $project_name.qa tfa $tfa_secret")->run();
    $this->taskExec("terminus secrets:set $project_name.dev tfa $tfa_secret")->run();

    $this->say("Bootstrap completed successfully.");
    $this->say("");
    $this->say("Next steps:");
    $this->say("1. Move the project to its final location:");
    $this->say("   mv .bootstrap ../$github_repo_name");
    $this->say("   mv .pantheon ../$github_repo_name/.pantheon");
    $this->say("");
    $this->say("2. Configure automatic deployment to Pantheon with GitHub Actions:");
    $this->say("   cd ../$github_repo_name");
    $this->say("   ddev robo deploy:config-autodeploy $terminus_token $github_token");
    $this->say("");
    $this->say("   This will generate SSH keys and provide instructions for:");
    $this->say("   - Setting up GitHub Secrets (TERMINUS_TOKEN, PANTHEON_DEPLOY_KEY, GH_TOKEN)");
    $this->say("   - Setting up GitHub Variables (PANTHEON_GIT_URL, ROLLBAR_SERVER_TOKEN)");
    $this->say("   - Adding the SSH public key to your Pantheon account");
    $this->say("");
    $this->say("For full deployment setup details, see:");
    $this->say("https://github.com/$github_organization/$github_repo_name#automatic-deployment-to-pantheon");
  }

  /**
   * Prepares the new GitHub repository for the project.
   *
   * @param string $project_name
   *   The Pantheon site name.
   * @param string $github_organization
   *   The GitHub organization.
   * @param string $github_repo_name
   *   The GitHub repository name.
   * @param string $github_repository_url
   *   The clone URL of the GitHub repository.
   */
  protected function prepareGithubRepository(string $project_name, string $github_organization, string $github_repo_name, string $github_repository_url) {
    $temp_remote = 'bootstrap_' . time();
    $this->taskExec("git remote add $temp_remote $github_repository_url")
      ->run();
    $this->taskExec("git push --force $temp_remote main")
      ->run();
    $this->taskExec("git remote remove $temp_remote")
      ->run();

    $this->taskExec("git clone $github_repository_url .bootstrap")
      ->run();

    if (!file_exists('.bootstrap')) {
      throw new \Exception("Failed to clone the GitHub repository. You might need to execute: `ddev auth ssh` beforehand.");
    }

    if (!file_exists('.bootstrap/.ddev/config.yaml')) {
      throw new \Exception("The GitHub repository is not in the expected format.");
    }

    $this->taskReplaceInFile('.bootstrap/robo-components/DeploymentTrait.php')
      ->from('Gizra/drupal-starter')
      ->to("$github_organization/$github_repo_name")
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('drupal-starter')
      ->to($github_repo_name)
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('8880')
      ->to((string) rand(6000, 8000))
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('4443')
      ->to((string) rand(3000, 5000))
      ->run();

    $host_user = $this->taskExec("whoami")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $this->taskReplaceInFile('.bootstrap/README.md')
      ->from('Drupal 9 Starter')
      ->to($project_name)
      ->run();

    $this->taskReplaceInFile('.bootstrap/README.md')
      ->from('Gizra')
      ->to($github_organization)
      ->run();

    $this->taskReplaceInFile('.bootstrap/README.md')
      ->from('drupal-starter')
      ->to($github_repo_name)
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/providers/pantheon.yaml')
      ->from('gizra-drupal-starter.qa')
      ->to($project_name . '.qa')
      ->run();

    $this->taskReplaceInFile('.bootstrap/composer.json')
      ->from('drupal-starter')
      ->to(strtolower($github_repo_name))
      ->run();
    $this->taskReplaceInFile('.bootstrap/composer.json')
      ->from('gizra')
      ->to(strtolower($github_organization))
      ->run();

    $this->taskReplaceInFile('.bootstrap/web/sites/default/settings.pantheon.php')
      ->from('drupal_starter')
      ->to(str_replace('-', '_', $github_repo_name))
      ->run();

    // Run composer install first to get contrib modules (needed for
    // merge-plugin to find webform's composer.libraries.json).
    $result = $this->taskExec("cd .bootstrap && composer install --no-interaction")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new \Exception("Failed to run composer install in GH repository.");
    }

    // Now update the lock file hash after the project name replacements.
    $result = $this->taskExec("cd .bootstrap && composer update --lock")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new \Exception("Failed to run composer update in GH repository.");
    }

    $this->taskReplaceInFile('.bootstrap/config/sync/system.site.yml')
      ->from('Drupal Starter')
      ->to($project_name)
      ->run();

    $result = $this->taskExec("cd .bootstrap && git add . && git commit -m 'Bootstrap project $project_name by $host_user' && git push origin main")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception("Failed to push to GH repository the result of the transformation.");
    }
  }

  /**
   * Creates and prepare the Pantheon project.
   *
   * @param string $terminus_token
   *   The Pantheon machine token.
   * @param string $project_name
   *   The Pantheon site name.
   */
  public function createPantheonProject(string $terminus_token, string $project_name) {
    $result = $this->taskExec("terminus auth:login --machine-token=\"$terminus_token\"")
      ->run()
      ->getExitCode();
    if ($result !== 0) {
      throw new \Exception("Failed to login to Terminus.");
    }

    $organizations = $this->taskExec("terminus org:list --format=json")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $organizations = json_decode($organizations, TRUE);

    // Prompt the user to select an organization.
    $io = new ConsoleIO($this->input(), $this->output());
    $organization_choices = array_combine(array_column($organizations, 'id'), array_column($organizations, 'label'));
    if (count($organization_choices) === 0) {
      throw new \Exception("No organization found.");
    }
    elseif (count($organization_choices) === 1) {
      $selected_organization_id = array_key_first($organization_choices);
    }
    else {
      $selected_organization_id = $io->choice('Select a Pantheon organization', $organization_choices);
    }

    // This upstream is the Drupal 10 base project what more or less
    // matches Drupal Starter.
    $upstream_id = "8a129104-9d37-4082-aaf8-e6f31154644e";

    $result = $this->taskExec("terminus site:create $project_name \"$project_name\" \"$upstream_id\" --org=\"$selected_organization_id\"")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception("Failed to create the Pantheon project.");
    }

    $result = $this->taskExec("terminus connection:set $project_name.dev git")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception("Failed to set the Pantheon project connection mode to Git.");
    }

    // Retrieve Git repository from Pantheon, then clone the artifact repository
    // to .pantheon directory.
    $pantheon_repository_url = $this->taskExec("terminus connection:info $project_name.dev --field=git_url")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    if (empty($pantheon_repository_url)) {
      throw new \Exception("Failed to retrieve the Pantheon project Git repository URL.");
    }

    $this->taskExec("git clone $pantheon_repository_url -b master .pantheon")
      ->run();

    // Ensure the dev dependencies are installed before compiling the theme in
    // case this is a retry.
    $this->taskExec('composer install')->run();

    // Compile theme.
    $this->themeCompile();

    // Remove the dev dependencies before pushing up to Pantheon.
    $this->taskExec("composer install --no-dev")->run();

    $rsync_exclude_string = '--exclude=' . implode(' --exclude=', self::$deploySyncExcludes);

    $result = $this->_exec("rsync -az -q $rsync_exclude_string .bootstrap/ .pantheon")->getExitCode();
    if ($result !== 0) {
      throw new \Exception('Failed to rsync .bootstrap to .pantheon');
    }

    // We need a working DDEV instance to compile the theme, that's why
    // it is a bit awkward to assemble the Pantheon artifact repository
    // from two GitHub repositories.
    $result = $this->_exec("rsync -az -q $rsync_exclude_string " . self::$themeBase . "/ .pantheon/" . self::$themeBase)->getExitCode();
    if ($result !== 0) {
      throw new \Exception('Failed to rsync theme to .pantheon');
    }

    $this->taskWriteToFile('.pantheon/.gitignore')
      ->append(FALSE)
      ->textFromFile('pantheon_template/gitignore-template')
      ->run();
    $this->taskWriteToFile('.pantheon/pantheon.yml')
      ->append(FALSE)
      ->textFromFile('pantheon_template/pantheon.yml')
      ->run();
    $this->taskWriteToFile('.pantheon/web/sites/default/settings.pantheon.php')
      ->append(FALSE)
      ->textFromFile('pantheon_template/settings.pantheon.php')
      ->run();
    $this->taskWriteToFile('.pantheon/web/sites/default/default.settings.php')
      ->textFromFile('pantheon_template/default.settings.php')
      ->run();
    $this->taskWriteToFile('.pantheon/web/sites/default/settings.php')
      ->append(FALSE)
      ->textFromFile('.bootstrap/web/sites/default/settings.pantheon.php')
      ->run();

    $result = $this->taskExec("cd .pantheon && git add . && git commit -m 'Bootstrap project $project_name' && git push origin master")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to push to Pantheon.');
    }

    // Create QA environment on Pantheon.
    $result = $this->taskExec("terminus multidev:create $project_name.dev qa")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to create the Pantheon QA environment.');
    }

    $result = $this->taskExec("terminus connection:set $project_name.qa git")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to set the Pantheon QA environment connection mode to Git.');
    }

    $result = $this->taskExec("terminus solr:enable $project_name")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to enable Solr.');
    }
  }

  /**
   * Lock all Pantheon environments for the given site.
   *
   * @param string $project_name
   *   The Pantheon site name.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password.
   */
  public function lockPantheonEnvironments(string $project_name, string $http_basic_auth_user, string $http_basic_auth_password) {
    if (empty($http_basic_auth_user) || empty($http_basic_auth_password)) {
      $this->say("No HTTP basic auth credentials were provided. Pantheon environments will not be locked.");
      return;
    }
    $pantheon_environments = $this->taskExec("terminus env:list $project_name --field=ID --format=list")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $pantheon_environments = explode(PHP_EOL, $pantheon_environments);
    foreach ($pantheon_environments as $pantheon_environment) {
      $result = $this->taskExec("terminus env:wake $project_name.$pantheon_environment")
        ->run()
        ->getExitCode();
      if ($result !== 0) {
        $this->say("Failed to wake up the Pantheon $pantheon_environment environment.");
        continue;
      }
      $result = $this->taskExec("terminus lock:enable $project_name.$pantheon_environment $http_basic_auth_user $http_basic_auth_password")
        ->run()
        ->getExitCode();
      if ($result !== 0) {
        $this->say("Failed to lock the Pantheon $pantheon_environment environment.");
      }
    }
  }

  /**
   * Verify the input data / environment.
   *
   * @param string $project_name
   *   The project name.
   * @param string $github_organization
   *   The GitHub organization.
   * @param string $github_repo_name
   *   The GitHub repository name.
   * @param string $terminus_token
   *   The Pantheon machine token.
   * @param string $github_token
   *   The GitHub token.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password.
   */
  protected function verifyRequirements(string $project_name, string $github_organization, string $github_repo_name, string $terminus_token, string $github_token, $http_basic_auth_user, $http_basic_auth_password) {
    if (is_dir('.bootstrap')) {
      throw new \Exception('The .bootstrap directory already exists. Please remove / move it and try again.');
    }
    if (is_dir('.pantheon')) {
      throw new \Exception('The .pantheon directory already exists. Please remove / move it and try again.');
    }
    if (empty(trim($project_name))) {
      throw new \Exception('The project name is empty.');
    }
    $this->validatePantheonSiteName($project_name);

    if (empty(trim($github_organization))) {
      throw new \Exception('The GitHub organization is empty.');
    }
    if (empty(trim($github_repo_name))) {
      throw new \Exception('The GitHub repository name is empty.');
    }
    if (empty(trim($terminus_token))) {
      throw new \Exception('The Pantheon machine token is empty.');
    }
    if (empty(trim($github_token))) {
      throw new \Exception('The GitHub token is empty.');
    }
  }

  /**
   * Validates a Pantheon site name.
   *
   * @param string $site_name
   *   The site name to validate.
   *
   * @throws \Exception
   *   If the site name is invalid.
   */
  protected function validatePantheonSiteName(string $site_name): void {
    if (strlen($site_name) >= 52) {
      throw new \Exception("The site name '$site_name' must be fewer than 52 characters.");
    }
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]$|^[a-zA-Z0-9]$/', $site_name)) {
      throw new \Exception("The site name '$site_name' can only contain a-z, A-Z, 0-9, and dashes, and cannot begin or end with a dash.");
    }
  }

}
