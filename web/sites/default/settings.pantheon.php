<?php

/**
 * @file
 * Pantheon-specific settings.
 */

use Pantheon\Integrations\Assets;
use Drupal\Core\Installer\InstallerKernel;

// Naive mitigation of bad traffic.
// These IPs below are from
// https://www.projecthoneypot.org/list_of_ips.php
// Use `ddev robo security:check-ddos`
// to detect malicious IPs.
$block_list = [
  '159.192.123.2',
  '94.156.71.97',
  '103.19.116.88',
];

if (in_array($_SERVER['REMOTE_ADDR'], $block_list)) {
  header('HTTP/1.0 403 Forbidden');
  exit;
}

$settings['container_yamls'][] = __DIR__ . '/services.pantheon.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * N.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && file_exists(Assets::dir() . "/settings.pantheon.php")) {
  include Assets::dir() . "/settings.pantheon.php";
}

$settings['config_sync_directory'] = '../config/sync';

/**
 * If there is a local settings file, then include it.
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}
$pantheon_env = getenv('PANTHEON_ENVIRONMENT');
$pantheon_site_name = getenv('PANTHEON_SITE_NAME');
$base_private_dir = '../config/elasticsearch';
$settings['site_id'] = 'drupal_starter';
if (file_exists($base_private_dir . '/' . $settings['site_id'] . '.es.secrets.json')) {
  $es_credentials = json_decode(file_get_contents($base_private_dir . '/' . $settings['site_id'] . '.es.secrets.json'), TRUE);
  if (is_array($es_credentials)) {
    $fallback = 'dev';
    $env = str_replace('-', '_', !empty($pantheon_env) ? $pantheon_env : $fallback);

    if (!isset($es_credentials[$env])) {
      $env = $fallback;
    }
    $_ENV['es_env'] = $env;

    // The port number is MANDATORY, even if it's the default one.
    // Elastic.co these days put instances on default port, include :443
    // nevertheless at the end of the URL.
    $config['elasticsearch_connector.cluster.server']['url'] = 'https://REPLACE-WITH-REAL-URL.us-central1.gcp.cloud.es.io:443';
    $config['elasticsearch_connector.cluster.server']['options']['use_authentication'] = TRUE;

    if (isset($es_credentials[$env])) {
      $config['elasticsearch_connector.cluster.server']['options']['username'] = $settings['site_id'] . '_' . $env;
      $config['elasticsearch_connector.cluster.server']['options']['password'] = $es_credentials[$env];
    }
  }
}
if (!empty($pantheon_env)) {
  // Rollbar settings for LIVE and TEST.
  if ($pantheon_env == 'live' || $pantheon_env == 'test') {
    $config['rollbar.settings']['environment'] = $pantheon_site_name . '.' . $pantheon_env;
    $config['rollbar.settings']['enabled'] = TRUE;
    /* Placeholders for adding the actual access token values.
     * $config['rollbar.settings']['access_token'] = '';
     * $config['rollbar.settings']['access_token_frontend'] = '';
     */
  }

  $config['environment_indicator.indicator']['name'] = strtoupper($pantheon_env);
  switch ($pantheon_env) {
    case 'test':
      $config['environment_indicator.indicator']['bg_color'] = '#ffcc6b';
      $config['environment_indicator.indicator']['fg_color'] = '#222222';
      break;

    case 'live':
      $config['environment_indicator.indicator']['bg_color'] = '#c81300';
      $config['environment_indicator.indicator']['fg_color'] = '#ffffff';

      /* Uncomment after going live.
       * $config['tfa.settings']['enabled'] = TRUE;
       */
      $config['crowdsec.settings']['env'] = 'prod';
      break;

    default:
      $config['environment_indicator.indicator']['bg_color'] = '#6e00ac';
      $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
      break;
  }
}

if (defined(
  'PANTHEON_ENVIRONMENT'
) && !InstallerKernel::installationAttempted(
) && extension_loaded('redis')) {
  // Set Redis as the default backend for any cache bin not otherwise specified.
  $settings['cache']['default'] = 'cache.backend.redis';

  // Phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';

  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host'] = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port'] = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password'] = $_ENV['CACHE_PASSWORD'];

  $settings['redis_compress_length'] = 100;
  $settings['redis_compress_level'] = 1;

  $settings['cache_prefix']['default'] = 'pantheon-redis';

  // Use the database for forms.
  $settings['cache']['bins']['form'] = 'cache.backend.database';

  // Apply changes to the container configuration to make better use of Redis.
  // This includes using Redis for the lock and flood control systems, as well
  // as the cache tag checksum. Alternatively, copy the contents of that file
  // to your project-specific services.yml file, modify as appropriate, and
  // remove this line.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  // Allow the services to work before the Redis module itself is enabled.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

  // Manually add the classloader path, this is required for the container
  // cache bin definition below.
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

  // 30 days
  $settings['redis.settings']['perm_ttl'] = 2630000;
  $settings['redis.settings']['perm_ttl_config'] = 43200;
  $settings['redis.settings']['perm_ttl_data'] = 43200;
  $settings['redis.settings']['perm_ttl_default'] = 43200;
  $settings['redis.settings']['perm_ttl_entity'] = 172800;

  // Use redis for container cache.
  // The container cache is used to load the container definition itself, and
  // thus any configuration stored in the container itself is not available
  // yet. These lines force the container cache to use Redis rather than the
  // default SQL cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize',
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}

// Setting secrets for various contrib modules.
// @see https://docs.pantheon.io/guides/secrets
if (function_exists('pantheon_get_secret')) {
  $tfa_key = pantheon_get_secret('tfa_key');
  if (!empty($tfa_key)) {
    putenv('TFA_KEY="' . $tfa_key . "\"");
  }

  $openai_api_key = pantheon_get_secret('openai_api_key');
  if (!empty($openai_api_key)) {
    putenv('OPENAI_API_KEY"' . $openai_api_key . "\"");
  }
}

if (file_exists(__DIR__ . '/settings.fast404.php')) {
  include __DIR__ . '/settings.fast404.php';
}

require __DIR__ . '/../bot_trap_protection.php';

$config['search_api.index.server_dev']['server'] = 'pantheon_search';
// As we push to Solr config of DDEV to Pantheon as well, we disable it here.
$config['search_api.server.solr']['status'] = FALSE;

/**
 * State caching.
 *
 * State caching uses the cache collector pattern to cache all requested keys
 * from the state API in a single cache entry, which can greatly reduce the
 * amount of database queries. However, some sites may use state with a
 * lot of dynamic keys which could result in a very large cache.
 */
$settings['state_cache'] = TRUE;
