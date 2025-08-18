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
   * Checks DDOS attacks by identifying top requesters.
   *
   * @param string $env
   *   Environment name to check, defaults to live.
   * @param int $limit
   *   Maximum number of IPs to list.
   * @param string|null $custom_log_path
   *   Optional path to a custom log file to analyze instead of fetching from
   *   Pantheon.
   */
  public function securityCheckDdos(string $env = 'live', int $limit = 25, ?string $custom_log_path = NULL) {
    if (!file_exists($custom_log_path) && !empty($custom_log_path)) {
      $custom_log_path = '../' . $custom_log_path;
    }
    if ($custom_log_path && file_exists($custom_log_path)) {
      $this->say("Using custom log file: $custom_log_path");
      self::$accessLogPath = $custom_log_path;
    }
    else {
      $this->getLogFile($env);
    }

    if (!file_exists(self::$accessLogPath)) {
      $this->yell('Error: Log file does not exist at ' . self::$accessLogPath, 40, 'red');
      return;
    }

    $totalRequests = count(file(self::$accessLogPath));
    $this->say("Analyzing $totalRequests total requests");

    // Parse IPs from log file.
    $output = shell_exec("cat " . escapeshellarg(self::$accessLogPath) . " | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn");
    $lines = explode("\n", trim($output));

    // Ensure whois is installed.
    $this->taskExec("which whois > /dev/null || sudo apt install whois -y")
      ->run();

    $total_ips = count($lines);
    $this->say("Processing top $limit IP addresses out of $total_ips unique IPs...");

    // First, collect basic IP data without whois lookups.
    $basic_ip_data = [];
    foreach ($lines as $line) {
      if (empty(trim($line))) {
        continue;
      }

      [$count, $ip] = preg_split('/\s+/', trim($line), 2);

      if (!$this->isPublicIp($ip)) {
        continue;
      }

      $percent = ($count / $totalRequests) * 100;

      $basic_ip_data[] = [
        'count' => $count,
        'ip' => $ip,
        'host' => 'unknown',
        'percent' => round($percent, 2),
        'network' => "Unknown",
        'organization' => "Unknown",
      ];
    }

    // Only perform DNS and whois lookups on the top N IPs.
    $data = [];
    /** @var array<string, array{count: int, ips: int, organization: string}> $subnet_stats */
    $subnet_stats = [];

    // Only process the top $limit IPs with whois.
    $top_ips = array_slice($basic_ip_data, 0, $limit);

    foreach ($top_ips as $ip_data) {
      $ip = $ip_data['ip'];

      $host = gethostbyaddr($ip);
      $ip_data['host'] = $host;

      // Get network information using whois.
      $whois_info = shell_exec('whois ' . escapeshellarg($ip) . " 2>/dev/null | grep -i 'netname\|organization\|orgname\|cidr\|inetnum\|netblock' | head -5");

      // Extract CIDR or subnet.
      $network = "Unknown";
      if (preg_match('/CIDR:\s+([^\s]+)/', $whois_info, $matches)) {
        $network = $matches[1];
      }
      elseif (preg_match('/inetnum:\s+([^\s]+)/', $whois_info, $matches)) {
        $network = $matches[1];
      }
      elseif (preg_match('/NetBlock:\s+([^\s]+)/', $whois_info, $matches)) {
        $network = $matches[1];
      }

      // Get organization name.
      $organization = "Unknown";
      if (preg_match('/Organization:\s+(.+)/', $whois_info, $matches)) {
        $organization = trim($matches[1]);
      }
      elseif (preg_match('/OrgName:\s+(.+)/', $whois_info, $matches)) {
        $organization = trim($matches[1]);
      }
      elseif (preg_match('/netname:\s+(.+)/i', $whois_info, $matches)) {
        $organization = trim($matches[1]);
      }

      // Update IP data with network information.
      $ip_data['network'] = $network;
      $ip_data['organization'] = $organization;
      $data[] = $ip_data;

      // Aggregate network statistics.
      if (!isset($subnet_stats[$network])) {
        $subnet_stats[$network] = [
          'count' => 0,
          'ips' => 0,
          'organization' => $organization,
        ];
      }
      $subnet_stats[$network]['count'] += $ip_data['count'];
      $subnet_stats[$network]['ips']++;
    }

    // Sort network stats by count.
    $sort_callback = function (array $a, array $b): int {
      return $b['count'] - $a['count'];
    };
    // @phpstan-ignore-next-line argument.unresolvableType
    uasort($subnet_stats, $sort_callback);

    // Display IP statistics.
    $this->printReleaseNotesTitle("Top $limit IP Addresses");
    echo "| Count | IP Address | Host | % of Total | Network | Organization |\n";
    echo "|-------|------------|------|------------|---------|---------------|\n";
    foreach ($data as $row) {
      echo "| {$row['count']} | {$row['ip']} | {$row['host']} | {$row['percent']}% | {$row['network']} | {$row['organization']} |\n";
    }

    // Display network statistics.
    $this->printReleaseNotesTitle("Top " . min($limit, count($subnet_stats)) . " Networks");
    echo "| Network | Organization | Request Count | % of Total | Unique IPs |\n";
    echo "|---------|--------------|---------------|------------|------------|\n";

    $counter = 0;
    foreach ($subnet_stats as $network => $stats) {
      if ($counter++ >= $limit) {
        break;
      }

      $percent = round(($stats['count'] / $totalRequests) * 100, 2);
      echo "| $network | {$stats['organization']} | {$stats['count']} | {$percent}% | {$stats['ips']} |\n";
    }

    // Generate request distribution by hour.
    $this->printReleaseNotesTitle("Request Distribution by Hour");
    $hourly_distribution = shell_exec("cat " . escapeshellarg(self::$accessLogPath) . " | awk '{print $4}' | cut -d: -f2 | sort | uniq -c");
    echo "```\n$hourly_distribution\n```\n";

    // Generate request distribution by status code.
    $this->printReleaseNotesTitle("Status Code Distribution");
    $status_distribution = shell_exec("cat " . escapeshellarg(self::$accessLogPath) . " | awk '{print $9}' | sort | uniq -c | sort -rn");
    echo "```\n$status_distribution\n```\n";

    // Clean up if we fetched the log file.
    if (!$custom_log_path) {
      unlink(self::$accessLogPath);
    }
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
    $this->_exec('$(terminus connection:info --field=sftp_command ' . $pantheon_info['name'] . ".$env) <<EOF
cd logs/nginx
get nginx-access.log " . self::$accessLogPath . "
EOF
");
    if (!file_exists(self::$accessLogPath)) {
      $this->say('Try to use "ddev auth ssh" first to fix this issue. It will let DDEV download the logs via SFTP with the keys of the host system.');
      $this->say('https://ddev.readthedocs.io/en/stable/users/usage/cli/#ssh-into-containers');
      throw new \Exception('Failed to download the logfiles');
    }
  }

  /**
   * Makes sure the IP is public.
   *
   * @param string $ip
   *   IPv4 or IPv6 address.
   *
   * @return bool
   *   TRUE if the IP is public, FALSE otherwise.
   */
  protected function isPublicIp(string $ip): bool {
    // Check if the IP is a valid IP address.
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
      return FALSE;
    }

    // Handle IPv6 addresses.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      // Check for IPv6 private ranges.
      $ipv6_private_ranges = [
        // Unique Local Addresses (ULA)
        'fc00::/7',
        // Link-local addresses.
        'fe80::/10',
        // Loopback.
        '::1/128',
        // Unspecified address.
        '::/128',
        // IPv4-mapped addresses.
        '::ffff:0:0/96',
        // Documentation prefix.
        '2001:db8::/32',
        // Multicast.
        'ff00::/8',
      ];

      // Convert IPv6 to binary representation.
      $binary = inet_pton($ip);
      if ($binary === FALSE) {
        return FALSE;
      }

      foreach ($ipv6_private_ranges as $range) {
        [$subnet, $mask] = explode('/', $range);
        $subnet_binary = inet_pton($subnet);

        // Compare the appropriate number of bits.
        $matches = TRUE;
        $mask_int = (int) $mask;
        for ($i = 0; $i < intdiv($mask_int, 8); $i++) {
          if (isset($binary[$i]) && isset($subnet_binary[$i]) && $binary[$i] !== $subnet_binary[$i]) {
            $matches = FALSE;
            break;
          }
        }

        // Check remaining bits if mask is not a multiple of 8.
        if ($matches && $mask_int % 8 > 0) {
          $remaining_bits = $mask_int % 8;
          $i = intdiv($mask_int, 8);
          if (isset($binary[$i]) && isset($subnet_binary[$i])) {
            $mask_byte = 0xFF00 >> $remaining_bits & 0xFF;
            $binary_ord = ord($binary[$i]);
            $subnet_binary_ord = ord($subnet_binary[$i]);
            if (($binary_ord & $mask_byte) !== ($subnet_binary_ord & $mask_byte)) {
              $matches = FALSE;
            }
          }
        }

        if ($matches) {
          return FALSE;
        }
      }

      return TRUE;
    }

    // Handle IPv4 addresses.
    $private_ranges = [
      '10.0.0.0/8',
      '172.16.0.0/12',
      '192.168.0.0/16',
      // Loopback.
      '127.0.0.0/8',
      // Link-local.
      '169.254.0.0/16',
      // Multicast.
      '224.0.0.0/4',
      // Reserved.
      '240.0.0.0/4',
      // Current network.
      '0.0.0.0/8',
      // Shared address space (RFC 6598)
      '100.64.0.0/10',
      // IETF Protocol Assignments.
      '192.0.0.0/24',
      // TEST-NET-1.
      '192.0.2.0/24',
      // Network benchmark tests.
      '198.18.0.0/15',
      // TEST-NET-2.
      '198.51.100.0/24',
      // TEST-NET-3.
      '203.0.113.0/24',
    ];

    $ip_long = ip2long($ip);
    foreach ($private_ranges as $range) {
      [$subnet, $mask] = explode('/', $range);
      $subnet_long = ip2long($subnet);
      $mask_int = (int) $mask;
      $mask_long = ~((1 << (32 - $mask_int)) - 1);
      if (($ip_long & $mask_long) === ($subnet_long & $mask_long)) {
        return FALSE;
      }
    }

    // The IP address is public.
    return TRUE;
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

}
