<?php

namespace RoboComponents;

/**
 * Security / monitoring related Robo commands.
 */
trait SecurityTrait {

  /**
   * The path to the access log.
   *
   * @var string
   */
  public static string $accessLogPath = '/tmp/nginx-access.log';

  /**
   * Checks DDOS attacks by identifying top requester IP addresses.
   *
   * @param string $env
   *   Environment name to check, defaults to live.
   * @param int $limit
   *   Maximum number of IPs to list.
   */
  public function securityCheckDdos(string $env = 'live', int $limit = 25) {
    $this->getLogFile($env);
    $totalRequests = count(file(self::$accessLogPath));

    $output = shell_exec("cat " . self::$accessLogPath . " | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn | head -n $limit");
    $lines = explode("\n", trim($output));
    $data = [];

    foreach ($lines as $line) {
      [$count, $ip] = preg_split('/\s+/', trim($line));
      $host = gethostbyaddr($ip);
      $percent = ($count / $totalRequests) * 100;

      $data[] = [
        'count' => $count,
        'ip' => $ip,
        'host' => $host,
        'percent' => round($percent, 2),
      ];
    }

    unlink(self::$accessLogPath);

    echo "| Count | IP Address | Host | % of Total Requests |\n";
    echo "|-------|------------|------|---------------------|\n";
    foreach ($data as $row) {
      echo "| {$row['count']} | {$row['ip']} | {$row['host']} | {$row['percent']}% |\n";
    }
  }

  /**
   * Analyze security access log interactively.
   *
   * @param string $env
   *   Environment name to check, defaults to live.
   */
  public function securityAccessLogOverview(string $env = 'live') {
    $this->getLogFile($env);
    $this->taskExec("sudo apt install goaccess --yes")->run();
    $this->_exec("goaccess " . self::$accessLogPath);
    unlink(self::$accessLogPath);
  }

  /**
   * Retrieves the logfile from Pantheon.
   *
   * @param string $env
   *   Environment name to check.
   *
   * @throws \Exception
   *   Failed to download the logfiles.
   */
  protected function getLogFile(string $env) {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $this->_exec("$(terminus connection:info --field=sftp_command " . $pantheon_info['name'] . ".$env) <<EOF
cd logs/nginx
get nginx-access.log " . self::$accessLogPath . "
EOF
");
    if (!file_exists(self::$accessLogPath)) {
      throw new \Exception('Failed to download the logfiles');
    }
  }

}
