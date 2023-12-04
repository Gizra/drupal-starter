<?php

namespace RoboComponents;

/**
 * Security / monitoring related Robo commands.
 */
trait SecurityTrait {

  /**
   * Checks DDOS attacks by identifying top requester IP addresses.
   *
   * @param string $env
   *   Environment name to check, defaults to live.
   * @param int $limit
   *   Maximum number of IPs to list.
   */
  public function securityCheckDdos(string $env = 'live', int $limit = 25) {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $this->_exec("$(terminus connection:info --field=sftp_command " . $pantheon_info['name'] . ".$env) <<EOF
cd logs/nginx
get nginx-access.log /tmp/nginx-access.log
EOF
");
    if (!file_exists('/tmp/nginx-access.log')) {
      throw new \Exception('Failed to download the logfiles');
    }

    $totalRequests = count(file('/tmp/nginx-access.log'));

    $output = shell_exec("cat /tmp/nginx-access.log | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn | head -n $limit");
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

    unlink('/tmp/nginx-access.log');

    echo "| Count | IP Address | Host | % of Total Requests |\n";
    echo "|-------|------------|------|---------------------|\n";
    foreach ($data as $row) {
      echo "| {$row['count']} | {$row['ip']} | {$row['host']} | {$row['percent']}% |\n";
    }
  }

}
