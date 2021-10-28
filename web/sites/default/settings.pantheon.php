<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

$settings['config_sync_directory'] = '../config/sync';

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

$base_private_dir = '../config/elasticsearch';
$site_id = 'drupal_starter';
if (file_exists($base_private_dir . '/' . $site_id . '.es.secrets.json')) {
  $es_credentials = json_decode(file_get_contents($base_private_dir . '/' . $site_id . '.es.secrets.json'), TRUE);
  if (is_array($es_credentials)) {
    $fallback = 'dev';
    $pantheon_env = getenv('PANTHEON_ENVIRONMENT');
    $env = !empty($pantheon_env) ? $pantheon_env : $fallback;

    if (!isset($es_credentials[$env])) {
      $env = $fallback;
    }
    $_ENV['es_env'] = $env;

    $config['elasticsearch_connector.cluster.server']['url'] = 'https://REPLACE-WITH-REAL-URL.us-central1.gcp.cloud.es.io:9243';
    $config['elasticsearch_connector.cluster.server']['options']['use_authentication'] = TRUE;

    if (isset($es_credentials[$env])) {
      $config['elasticsearch_connector.cluster.server']['options']['username'] = $site_id . '_' . $env;
      $config['elasticsearch_connector.cluster.server']['options']['password'] = $es_credentials[$env];
    }
  }
}
