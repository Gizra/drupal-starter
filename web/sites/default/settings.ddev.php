<?php

/**
 * @file
 * Custom settings for DDEV. This file is not managed by DDEV.
 */

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/services.yml';
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

$host = "db";
$port = 3306;

// If DDEV_PHP_VERSION is not set but IS_DDEV_PROJECT *is*, it means we're running (drush) on the host,
// so use the host-side bind port on docker IP
if (empty(getenv('DDEV_PHP_VERSION') && getenv('IS_DDEV_PROJECT') == 'true')) {
  $host = "127.0.0.1";
  $port = 32796;
}

$databases['default']['default'] = array(
  'database' => "db",
  'username' => "db",
  'password' => "db",
  'host' => $host,
  'driver' => "mysql",
  'port' => $port,
  'prefix' => "",
);

// Migrate source database.
$ddev_migrate_remote_source = getenv('DDEV_MIGRATE_REMOTE_SOURCE');
if (!empty($ddev_migrate_remote_source) && gethostbyname($ddev_migrate_remote_source) !== $ddev_migrate_remote_source) {
  $databases['migrate']['default'] = [
    'database' => 'db',
    'username' => 'db',
    'password' => 'db',
    'prefix' => '',
    'host' => $ddev_migrate_remote_source,
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  ];
}

// Fake migrate default source to eliminate a warning about missing
// database connection.
// @todo: replace it with real, external credentials if needed.
$databases['migrate']['default'] = $databases['default']['default'];

$settings['hash_salt'] = 'ETXSRhodvuWLJsBUnpgkRpTXOLqbuozKXwjwZkuGiHSCpdEQLHXgdgGUHeCVHnXv';

// This will prevent Drupal from setting read-only permissions on sites/default.
$settings['skip_permissions_hardening'] = TRUE;

// This will ensure the site can only be accessed through the intended host
// names. Additional host patterns can be added for custom configurations.
$settings['trusted_host_patterns'] = ['.*'];

// Don't use Symfony's APCLoader. ddev includes APCu; Composer's APCu loader has
// better performance.
$settings['class_loader_auto_detect'] = FALSE;

if (isset($app_root) && isset($site_path)) {
  if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
    include $app_root . '/' . $site_path . '/settings.local.php';
  }
}


// Custom settings.
$settings['file_private_path'] = '/var/www/private';
$settings['config_sync_directory'] = '../config/sync';
$config['config_split.config_split.dev']['status'] = TRUE;

// Environment Indicator.
$config['environment_indicator.indicator']['name'] = 'Local';
$config['environment_indicator.indicator']['bg_color'] = '#00a073';
$config['environment_indicator.indicator']['fg_color'] = '#fff';

// SMTP settings. Use Mail Hog (`ddev describe` to get the URL) to see the sent
// mails.
$config['smtp.settings']['smtp_host'] = 'localhost';
$config['smtp.settings']['smtp_port'] = '1025';

$config['system.logging']['error_level'] = 'verbose';

$settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
$settings['redis.connection']['host']      = 'redis';  // Your Redis instance hostname.
$settings['cache']['default'] = 'cache.backend.redis';
$settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
$settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

/**
 * State caching.
 *
 * State caching uses the cache collector pattern to cache all requested keys
 * from the state API in a single cache entry, which can greatly reduce the
 * amount of database queries. However, some sites may use state with a
 * lot of dynamic keys which could result in a very large cache.
 */
$settings['state_cache'] = TRUE;

//$config['system.performance']['css']['preprocess'] = FALSE;
//$config['system.performance']['js']['preprocess'] = FALSE;

// Excludes modules from configuration export, as they should not be enabled on
// production.
$settings['config_exclude_modules'] = [
  'devel',
  'webprofiler',
  'stage_file_proxy',
];

if (file_exists(__DIR__ . '/settings.fast404.php')) {
  include __DIR__ . '/settings.fast404.php';
}

require __DIR__ . '/../bot_trap_protection.php';

// Disable CrowdSec's "whisper" locally. So one doesn't get blocked locally, or PHPUnit can work well.
$config['crowdsec.settings']['whisper']['enable'] = 0;
