<?php

namespace RoboComponents;

/**
 * Provision ElasticSearch with all the indices and users, custom analyzer..
 */
trait ElasticSearchTrait {

  /**
   * ElasticSearch index prefix.
   *
   * @var string
   */
  private static string $indexPrefix = 'elasticsearch_index_pantheon_';


  /**
   * ElasticSearch index names.
   *
   * @var array|string[]
   */
  private array $indices = [
    "server",
  ];

  /**
   * ElasticSearch environments - we will at least one index per environment.
   *
   * @var array|string[]
   */
  private array $environments = ["qa", "dev", "test", "live"];

  /**
   * Identifies of the sites in ElasticSearch.
   *
   * @var array|string[]
   */
  private array $sites = ["server"];

  /**
   * Generates a cryptographically secure random string for the password.
   *
   * @param int $length
   *   Length of the random string.
   * @param string $keyspace
   *   The set of characters that can be part of the output string.
   *
   * @return string
   *   The random string.
   *
   * @throws \Exception
   */
  protected function randomStr(
    int $length = 64,
    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  ): string {
    if ($length < 1) {
      throw new \RangeException("Length must be a positive integer");
    }
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
      $pieces[] = $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
  }

  /**
   * Provision command.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string|null $environment
   *   The environment ID. To test changes in the index config selectively.
   *
   * @throws \Exception
   */
  public function elasticsearchProvision(string $es_url, string $username, string $password, ?string $environment = NULL): void {
    $needs_users = TRUE;

    $es_url = rtrim($es_url, '/');
    if (strstr($es_url, '//elasticsearch:') !== FALSE) {
      // Detect DDEV.
      self::$indexPrefix = 'elasticsearch_index_db_';
      $needs_users = FALSE;
    }
    else {
      $result = json_decode($this
        ->taskExec("curl -u $username:$password $es_url/_security/user")
        ->printOutput(FALSE)
        ->run()
        ->getMessage(), TRUE);
      if (isset($result['error'])) {
        throw new \Exception('Cannot connect to ES or security not enabled');
      }
      foreach (array_keys($result) as $existing_username) {
        foreach ($this->sites as $site) {
          if (strstr($existing_username, $site) !== FALSE) {
            // Users do exist with the site name.
            $needs_users = FALSE;
            break 2;
          }
        }

      }
    }

    $index_creation = $this->taskParallelExec();
    $role_creation = $this->taskParallelExec();
    $user_creation = $this->taskParallelExec();
    $credentials = [];
    if (!empty($environment)) {
      $this->environments = [$environment];
    }
    foreach ($this->environments as $environment) {
      $environment = str_replace('-', '_', $environment);
      foreach ($this->indices as $index) {
        $index_creation->process("curl -u $username:$password -X PUT $es_url/" . self::$indexPrefix . "{$index}_$environment");
      }
      foreach ($this->sites as $site) {
        if (!isset($credentials[$site])) {
          $credentials[$site] = [];
        }
        if (!isset($credentials[$site][$environment])) {
          $credentials[$site][$environment] = [];
        }
        $allowed_indices = [];
        foreach ($this->indices as $index) {
          if (strstr($index, $site) !== FALSE) {
            $allowed_indices[] = '"' . self::$indexPrefix . $index . '_' . $environment . '"';
          }
        }
        $allowed_indices = implode(',', $allowed_indices);

        $role_data = <<<END
{ "cluster": ["all"],
  "indices": [
    {
      "names": [ $allowed_indices ],
      "privileges": ["all"]
    }
  ]
}
END;

        $role_creation->process("curl -u $username:$password -X POST $es_url/_security/role/${site}_$environment -H 'Content-Type: application/json' --data '$role_data'");

        // Generate random password or re-use an existing one from the JSON.
        $existing_password = $this->getUserPassword($site, $environment);
        $user_pw = !empty($existing_password) ? $existing_password : $this->randomStr();
        $user_data = <<<END
{ "password" : "$user_pw",
  "roles": [ "{$site}_$environment" ]
}
END;
        $credentials[$site][$environment] = $user_pw;
        $user_creation->process("curl -u $username:$password -X POST $es_url/_security/user/{$site}_$environment -H 'Content-Type: application/json' --data '$user_data'");
      }

    }

    $index_creation->run();
    if ($needs_users) {
      $role_creation->run();
      $user_creation->run();

      // We expose the credentials as files on the system.
      // Should be securely handled and deleted after the execution.
      foreach ($credentials as $site => $credential_per_environment) {
        file_put_contents($site . '.es.secrets.json', json_encode($credential_per_environment));
      }
    }

    $this->elasticsearchAnalyzer($es_url, $username, $password);
  }

  /**
   * Apply / actualize the default analyzer.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   *
   * @throws \Exception
   */
  public function elasticsearchAnalyzer(string $es_url, string $username = '', string $password = ''): void {
    $analyzer_data = <<<END
{
  "analysis": {
    "analyzer": {
      "default": {
        "type": "custom",
        "char_filter":  [ "html_strip" ],
        "tokenizer": "standard",
        "filter": [ "lowercase" ]
      }
    }
  }
}
END;

    $this->applyIndexSettings($es_url, $username, $password, $analyzer_data);
  }

  /**
   * Apply index configuration snippet to all indices.
   *
   * @param string $es_url
   *   Fully qualified URL to ES, for example: http://elasticsearch:9200 .
   * @param string $username
   *   The username of the ES admin user.
   * @param string $password
   *   The password of the ES admin user.
   * @param string $data
   *   The JSON snippet to apply.
   */
  private function applyIndexSettings(string $es_url, string $username, string $password, string $data): void {
    foreach ($this->environments as $environment) {
      $environment = str_replace('-', '_', $environment);
      foreach ($this->indices as $index) {
        $this->taskExec("curl -u $username:$password -X POST $es_url/" . self::$indexPrefix . "{$index}_$environment/_close")->run();
        $this->taskExec("curl -u $username:$password -X PUT $es_url/" . self::$indexPrefix . "{$index}_$environment/_settings -H 'Content-Type: application/json' --data '$data'")->run();
        $this->taskExec("curl -u $username:$password -X POST $es_url/" . self::$indexPrefix . "{$index}_$environment/_open")->run();
      }
    }
  }

  /**
   * Returns an already existing password for the given user.
   *
   * @param string $site
   *   The site ID.
   * @param string $environment
   *   The environment ID.
   *
   * @return string|null
   *   The password of the user in ElasticSearch, if exists.
   */
  protected function getUserPassword(string $site, string $environment): ?string {
    $credentials_file = $site . '.es.secrets.json';
    if (!file_exists($credentials_file)) {
      return NULL;
    }
    $credentials = file_get_contents($credentials_file);
    if (empty($credentials)) {
      return NULL;
    }
    $credentials = json_decode($credentials, TRUE);
    if (!is_array($credentials)) {
      return NULL;
    }
    if (!isset($credentials[$environment])) {
      return NULL;
    }
    return $credentials[$environment];
  }

}
