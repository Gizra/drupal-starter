<?php

namespace RoboComponents;

/**
 * Automated way to launch a new client project from Drupal Starter.
 */
trait BootstrapTrait {

  /**
   * Bootstrap a new client project on Pantheon.io.
   *
   * @param string $github_repository
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
  public function bootstrapProject(string $github_repository, string $pantheon_project, string $terminus_token, string $github_token, string $docker_mirror_url = '', string $http_basic_auth_user = '', string $http_basic_auth_password = '') {

  }

}
