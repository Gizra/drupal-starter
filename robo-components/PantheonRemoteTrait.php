<?php

namespace RoboComponents;

/**
 * Easy CLI access to services (like MySQL or filesystem) at Pantheon.io.
 */
trait PantheonRemoteTrait {

  /**
   * Open an SFTP connection to a Pantheon environment.
   *
   * @param string $env
   *   The environment to connect to (dev, test, live, or a multidev name).
   */
  public function pantheonConnectSftp(string $env = 'dev'): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    // Get the SFTP connection info.
    $result = $this->taskExec("terminus connection:info $pantheon_terminus_environment --field=sftp_command")
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      throw new \Exception("Could not get SFTP connection information for $pantheon_terminus_environment");
    }

    $sftp_command = trim($result->getMessage());

    $this->executeCommand($sftp_command);
  }

  /**
   * Open a MySQL connection to a Pantheon environment.
   *
   * @param string $env
   *   The environment to connect to (dev, test, live, or a multidev name).
   */
  public function pantheonConnectSql(string $env = 'dev'): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    // Get the MySQL connection info.
    $result = $this->taskExec("terminus connection:info $pantheon_terminus_environment --field=mysql_command")
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      throw new \Exception("Could not get MySQL connection information for $pantheon_terminus_environment");
    }

    $mysql_command = trim($result->getMessage());
    // Do not try to read all table data ahead.
    $mysql_command = str_replace('mysql ', 'mysql -A ', $mysql_command);

    $this->executeCommand($mysql_command);
  }

  /**
   * Open a Redis connection to a Pantheon environment.
   *
   * @param string $env
   *   The environment to connect to (dev, test, live, or a multidev name).
   */
  public function pantheonConnectRedis(string $env = 'dev'): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    // Get the Redis connection info.
    $result = $this->taskExec("terminus connection:info $pantheon_terminus_environment --field=redis_command")
      ->printOutput(FALSE)
      ->run();

    if ($result->getExitCode() !== 0) {
      throw new \Exception("Could not get Redis connection information for $pantheon_terminus_environment");
    }

    $redis_command = trim($result->getMessage());

    if (empty($redis_command)) {
      throw new \Exception("Redis connection information for $pantheon_terminus_environment is empty, likely this site does not have Redis add-on.");
    }

    $this->executeCommand($redis_command);
  }

  /**
   * Get connection info for a Pantheon environment.
   *
   * @param string $env
   *   The environment to get info for (dev, test, live, or a multidev name).
   * @param string $type
   *   The connection type (sftp, mysql, redis, or all).
   */
  public function pantheonConnectInfo(string $env = 'dev', string $type = 'all'): void {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $pantheon_terminus_environment = $pantheon_info['name'] . '.' . $env;

    $fields = '';
    switch ($type) {
      case 'sftp':
        $fields = '--fields=sftp_*';
        break;

      case 'mysql':
      case 'sql':
        $fields = '--fields=mysql_*';
        break;

      case 'redis':
        $fields = '--fields=redis_*';
        break;

      case 'git':
        $fields = '--fields=git_*';
        break;
    }

    $result = $this->taskExec("terminus connection:info $pantheon_terminus_environment $fields")
      ->run();

    if ($result->getExitCode() !== 0) {
      throw new \Exception("Could not get connection information for $pantheon_terminus_environment");
    }
  }

  /**
   * Execute a command using the appropriate method based on stdin status.
   *
   * Uses passthru() when input is piped (for stdin support) or _exec() for
   * interactive commands (for better Robo integration and output formatting).
   */
  private function executeCommand(string $command): void {
    // We want to make the following working:
    // echo "select * from node" | ddev robo ...
    // That's why simple ->_exec does not fit entirely.
    if (!posix_isatty(STDIN)) {
      passthru($command);
    }
    else {
      // Interactive mode, use Robo's _exec for better experience.
      $this->_exec($command);
    }
  }

}
