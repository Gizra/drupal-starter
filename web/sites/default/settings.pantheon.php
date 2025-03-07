<?php

/**
 * @file
 * Pantheon-specific settings.
 */

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
include_once __DIR__ . "/settings.pantheon.php";

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

if (!empty($pantheon_env) && !empty($_ENV['CACHE_HOST'])) {
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  // Phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';
  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host'] = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port'] = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password'] = $_ENV['CACHE_PASSWORD'];

  $settings['redis_compress_length'] = 100;
  $settings['redis_compress_level'] = 1;

  // Use Redis as the default cache.
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['cache_prefix']['default'] = 'pantheon-redis';

  // Use the database for forms.
  $settings['cache']['bins']['form'] = 'cache.backend.database';
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

$config['search_api.index.server_dev']['server'] = 'pantheon_solr8';
// As we push to Solr config of DDEV to Pantheon as well, we disable it here.
$config['search_api.server.solr']['status'] = FALSE;
