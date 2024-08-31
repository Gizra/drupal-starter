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
   *   The project name.
   * @param string $github_repository_url
   *   The clone URL of the GitHub repository.
   * @param string $terminus_token
   *   The Pantheon machine token.
   * @param string $github_token
   *   The GitHub personal access token for a user with access to this project.
   * @param string $docker_mirror_url
   *   The Docker mirror URL. Optional, but expect Travis failures if not set,
   *   this is due to rate limiting on Docker Hub.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user. Optional. If set, all the Pantheon environments
   *   will be protected with HTTP basic auth.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password. Optional.
   */
  public function bootstrapProject(string $project_name, string $github_repository_url, string $terminus_token, string $github_token, string $docker_mirror_url = '', string $http_basic_auth_user = '', string $http_basic_auth_password = '') {
    // Extract project name from $github_repository_url.
    // The syntax is like: git@github.com:Organization/projectname.git .
    preg_match('/github.com[:\/](.*)\/(.*)\.git/', $github_repository_url, $matches);
    $github_organization = $matches[1];
    $project_machine_name = $matches[2];

    $this->verifyRequirements($project_name, $github_organization, $project_machine_name, $terminus_token, $github_token, $docker_mirror_url, $http_basic_auth_user, $http_basic_auth_password);

    $this->prepareGithubRepository($project_name, $github_organization, $project_machine_name, $github_repository_url, $docker_mirror_url);

    $this->createPantheonProject($terminus_token, $project_name, $project_machine_name);

    $this->deployPantheonInstallEnv('dev', $project_machine_name);
    $this->deployPantheonInstallEnv('qa', $project_machine_name);

    $this->lockPantheonEnvironments($project_machine_name, $http_basic_auth_user, $http_basic_auth_password);

    $tfa_secret = $this->taskExec("openssl rand -base64 32")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $this->taskExec('terminus self:plugin:install pantheon-systems/terminus-secrets-plugin')->run();
    $this->taskExec("terminus secrets:set $project_machine_name.qa tfa $tfa_secret")->run();
    $this->taskExec("terminus secrets:set $project_machine_name.dev tfa $tfa_secret")->run();

    $this->say("Bootstrap completed successfully.");
    $this->say("You might want to run the following commands to properly place the project:");
    $this->say("mv .bootstrap ../$project_machine_name");
    $this->say("mv .pantheon ../$project_machine_name/.pantheon");
    $this->say("To configure autodeployment to pantheon run:");
    $this->say("ddev robo deploy:config-autodeploy $terminus_token, $github_token");
  }

  /**
   * Prepares the new GitHub repository for the project.
   *
   * @param string $project_name
   *   The project name.
   * @param string $organization
   *   The GitHub organization.
   * @param string $project_machine_name
   *   The project machine name in GH slug.
   * @param string $github_repository_url
   *   The clone URL of the GitHub repository.
   * @param string $docker_mirror_url
   *   The Docker mirror URL. Optional, but expect Travis failures if not set.
   */
  protected function prepareGithubRepository(string $project_name, string $organization, string $project_machine_name, string $github_repository_url, string $docker_mirror_url = '') {
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
      ->to("$organization/$project_machine_name")
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('drupal-starter')
      ->to($project_machine_name)
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
      ->to($organization)
      ->run();

    $this->taskReplaceInFile('.bootstrap/README.md')
      ->from('drupal-starter')
      ->to($project_machine_name)
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/providers/pantheon.yaml')
      ->from('gizra-drupal-starter.qa')
      ->to($project_name . '.qa')
      ->run();

    $this->taskReplaceInFile('.bootstrap/composer.json')
      ->from('drupal-starter')
      ->to(strtolower($project_machine_name))
      ->run();
    $this->taskReplaceInFile('.bootstrap/composer.json')
      ->from('gizra')
      ->to(strtolower($organization))
      ->run();

    $this->taskReplaceInFile('.bootstrap/web/sites/default/settings.pantheon.php')
      ->from('drupal_starter')
      ->to(str_replace('-', '_', $project_machine_name))
      ->run();

    $this->taskReplaceInFile('.bootstrap/.travis.template.yml')
      ->from('DOCKER_MIRROR')
      ->to($docker_mirror_url)
      ->run();

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
   *   The project name.
   * @param string $project_machine_name
   *   The project machine name in GH slug.
   */
  public function createPantheonProject(string $terminus_token, string $project_name, string $project_machine_name) {
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
    $upstream_id = "bde48795-b16d-443f-af01-8b1790caa1af";

    $result = $this->taskExec("terminus site:create $project_machine_name \"$project_name\" \"$upstream_id\" --org=\"$selected_organization_id\"")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception("Failed to create the Pantheon project.");
    }

    $result = $this->taskExec("terminus connection:set $project_machine_name.dev git")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception("Failed to set the Pantheon project connection mode to Git.");
    }

    // Retrieve Git repository from Pantheon, then clone the artifact repository
    // to .pantheon directory.
    $pantheon_repository_url = $this->taskExec("terminus connection:info $project_machine_name.dev --field=git_url")
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
    $result = $this->taskExec("terminus multidev:create $project_machine_name.dev qa")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to create the Pantheon QA environment.');
    }

    $result = $this->taskExec("terminus connection:set $project_machine_name.qa git")
      ->run()
      ->getExitCode();

    if ($result !== 0) {
      throw new \Exception('Failed to set the Pantheon QA environment connection mode to Git.');
    }
  }

  /**
   * Lock all Pantheon environments for the given site.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password.
   */
  public function lockPantheonEnvironments(string $project_machine_name, string $http_basic_auth_user, string $http_basic_auth_password) {
    if (empty($http_basic_auth_user) || empty($http_basic_auth_password)) {
      $this->say("No HTTP basic auth credentials were provided. Pantheon environments will not be locked.");
      return;
    }
    $pantheon_environments = $this->taskExec("terminus env:list $project_machine_name --field=ID --format=list")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $pantheon_environments = explode(PHP_EOL, $pantheon_environments);
    foreach ($pantheon_environments as $pantheon_environment) {
      $result = $this->taskExec("terminus env:wake $project_machine_name.$pantheon_environment")
        ->run()
        ->getExitCode();
      if ($result !== 0) {
        $this->say("Failed to wake up the Pantheon $pantheon_environment environment.");
        continue;
      }
      $result = $this->taskExec("terminus lock:enable $project_machine_name.$pantheon_environment $http_basic_auth_user $http_basic_auth_password")
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
   * @param string $organization
   *   The GitHub organization.
   * @param string $project_machine_name
   *   The project machine name in GH slug.
   * @param string $terminus_token
   *   The Pantheon machine token.
   * @param string $github_token
   *   The GitHub token.
   * @param string $docker_mirror_url
   *   The Docker mirror URL.
   * @param string $http_basic_auth_user
   *   The HTTP basic auth user.
   * @param string $http_basic_auth_password
   *   The HTTP basic auth password.
   */
  protected function verifyRequirements(string $project_name, string $organization, string $project_machine_name, string $terminus_token, string $github_token, string $docker_mirror_url, $http_basic_auth_user, $http_basic_auth_password) {
    if (is_dir('.bootstrap')) {
      throw new \Exception('The .bootstrap directory already exists. Please remove / move it and try again.');
    }
    if (is_dir('.pantheon')) {
      throw new \Exception('The .pantheon directory already exists. Please remove / move it and try again.');
    }
    if (empty(trim($project_name))) {
      throw new \Exception('The project name is empty.');
    }
    if (empty(trim($organization))) {
      throw new \Exception('The organization is empty.');
    }
    if (empty(trim($project_machine_name))) {
      throw new \Exception('The project machine name is empty.');
    }
    if (str_contains($project_machine_name, ' ')) {
      throw new \Exception('The project machine name contains spaces.');
    }
    if (empty(trim($terminus_token))) {
      throw new \Exception('The Pantheon machine token is empty.');
    }
    if (empty(trim($github_token))) {
      throw new \Exception('The GitHub token is empty.');
    }
    if (empty(trim($docker_mirror_url))) {
      throw new \Exception('The Docker mirror URL is empty.');
    }
    if (!empty($docker_mirror_url) && !filter_var($docker_mirror_url, FILTER_VALIDATE_URL)) {
      throw new \Exception('The Docker mirror URL is not a valid URL.');
    }
  }

}
