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
   * @param string $pantheon_project
   *   The Pantheon project name.
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
  public function bootstrapProject(string $project_name, string $github_repository_url, string $pantheon_project, string $terminus_token, string $github_token, string $docker_mirror_url = '', string $http_basic_auth_user = '', string $http_basic_auth_password = '') {
    $temp_remote = 'bootstrap_' . time();
    $this->taskExec("git remote add $temp_remote $github_repository_url")
      ->run();
    $this->taskExec("git push --force $temp_remote main")
      ->run();
    $this->taskExec("git remote remove $temp_remote")
      ->run();

    $this->taskExec("git clone $github_repository_url .bootstrap")
      ->run();

    // Extract project name from $github_repository_url.
    // The syntax is like: git@github.com:Organization/projectname.git
    preg_match('/github.com[:\/](.*)\/(.*)\.git/', $github_repository_url, $matches);
    $organization = $matches[1];
    $project_machine_name = $matches[2];

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
      ->to($pantheon_project . '.qa')
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

    $this->taskExec("cd .bootstrap && git add . && git commit -m 'Bootstrap project $project_name by $host_user' && git push origin main")
      ->run();
  }

}
