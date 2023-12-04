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
      if (!$this->isPublicIp($ip)) {
        $this->yell("Skipping internal IP: $ip");
        continue;
      }
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

  /**
   * Makes sure the IP is public.
   *
   * @param string $ip
   *   IPv4 address.
   *
   * @return bool
   *   TRUE if the IP is public, FALSE otherwise.
   */
  protected function isPublicIp($ip) {
    // Check if the IP is a valid IP address and not a private or reserved IP.
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
      return FALSE;
    }

    // Check for loopback address (127.0.0.1)
    if (substr($ip, 0, 4) === '127.') {
      return FALSE;
    }

    // The IP address is public and not loopback.
    return TRUE;
  }

}
