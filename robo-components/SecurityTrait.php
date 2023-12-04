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
   */
  public function securityCheckDdos(string $env = 'live') {
    $pantheon_info = $this->getPantheonNameAndEnv();
    $this->_exec("$(terminus connection:info --field=sftp_command " . $pantheon_info['name'] . ".$env) <<EOF
cd logs/nginx
get nginx-access.log /tmp/nginx-access.log
EOF
");
    if (!file_exists('/tmp/nginx-access.log')) {
      throw new \Exception('Failed to download the logfiles');
    }
    $this->_exec("cat /tmp/nginx-access.log | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn | head -n 25");
    unlink('/tmp/nginx-access.log');
  }

}
