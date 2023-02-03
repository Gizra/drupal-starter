<?php

namespace RoboComponents;

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
    $organization = $matches[1];
    $project_machine_name = $matches[2];

    $this->prepareGithubRepository($project_name, $organization, $project_machine_name, $github_repository_url, $docker_mirror_url);

    $this->createPantheonProject($terminus_token, $project_name, $project_machine_name, $organization);

    $this->deployPantheonInstallEnv('dev', $project_machine_name);
    $this->deployPantheonInstallEnv('qa', $project_machine_name);

    if ($http_basic_auth_user && $http_basic_auth_password) {
      $this->lockPantheonEnvironments($project_machine_name, $http_basic_auth_user, $http_basic_auth_password);
    }
    else {
      $this->say("No HTTP basic auth credentials were provided. Pantheon environments will not be locked.");
    }
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
   *   The Docker mirror URL. Optional, but expect Travis failures if not set,
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

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('drupal-starter')
      ->to($project_machine_name)
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('8880')
      ->to(rand(6000, 8000))
      ->run();

    $this->taskReplaceInFile('.bootstrap/.ddev/config.yaml')
      ->from('4443')
      ->to(rand(3000, 5000))
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
      ->from('yourproject.dev')
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

    $this->taskExec("cd .bootstrap && composer update --lock")
      ->run();

    $this->taskReplaceInFile('.bootstrap/config/sync/system.site.yml')
      ->from('Drupal Starter')
      ->to($project_name)
      ->run();

    $this->taskExec("cd .bootstrap && git add . && git commit -m 'Bootstrap project $project_name by $host_user' && git push origin main")
      ->run();
  }

  /**
   * Creates and prepare the Pantheon project.
   *
   * @param $terminus_token
   *   The Pantheon machine token. @see https://pantheon.io/docs/machine-tokens
   *
   * @param $project_name
   *   The project name.
   * @param $project_machine_name
   *   The project machine name in GH slug.
   * @param $organization
   *   The GitHub/Pantheon organization.
   */
  protected function createPantheonProject($terminus_token, $project_name, $project_machine_name, $organization) {
    $this->taskExec("terminus auth:login --machine-token=\"$terminus_token\"")
      ->run();

    $this->taskExec("terminus site:create $project_machine_name \"$project_name\" \"Drupal 9\" --org=\"$organization\"")
      ->run();

    // Retrieve Git repository from Pantheon, then clone the artifact repository
    // to .pantheon directory.
    $pantheon_repository_url = $this->taskExec("terminus connection:info $project_machine_name.dev --field=git_url")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $this->taskExec("git clone $pantheon_repository_url .pantheon");

    // Create QA environment on Pantheon.
    $this->taskExec("terminus multidev:create $project_machine_name.dev qa")
      ->run();
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
    $pantheon_environments = $this->taskExec("terminus env:list $project_machine_name --field=ID --format=list")
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $pantheon_environments = explode(PHP_EOL, $pantheon_environments);
    foreach ($pantheon_environments as $pantheon_environment) {
      $this->taskExec("terminus env:wake $project_machine_name.$pantheon_environment")
        ->run();
      $this->taskExec("terminus env:lock $project_machine_name.$pantheon_environment --username=$http_basic_auth_user --password=$http_basic_auth_password")
        ->run();
    }
  }

}
