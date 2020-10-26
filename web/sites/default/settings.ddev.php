<?php

/**
 * @file
 * Custom settings for DDEV. This file is not managed by DDEV.
 */

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

$settings['hash_salt'] = 'ETXSRhodvuWLJsBUnpgkRpTXOLqbuozKXwjwZkuGiHSCpdEQLHXgdgGUHeCVHnXv';

// This will prevent Drupal from setting read-only permissions on sites/default.
$settings['skip_permissions_hardening'] = TRUE;

// This will ensure the site can only be accessed through the intended host
// names. Additional host patterns can be added for custom configurations.
$settings['trusted_host_patterns'] = ['.*'];

// Don't use Symfony's APCLoader. ddev includes APCu; Composer's APCu loader has
// better performance.
$settings['class_loader_auto_detect'] = FALSE;

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}


// Custom settings.
$settings['file_private_path'] = '/var/www/private';
$settings['config_sync_directory'] = '../config/sync';
$config['config_split.config_split.dev']['status'] = TRUE;
$config['environment_indicator.indicator']['bg_color'] = '#006600';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';

// SMTP settings. Use Mail Hog (`ddev describe` to get the URL) to see the sent
// mails.
$config['smtp.settings']['smtp_host'] = 'localhost';
$config['smtp.settings']['smtp_port'] = '1025';

$config['system.logging']['error_level'] = 'verbose';

$settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
$settings['redis.connection']['host']      = 'redis';  // Your Redis instance hostname.
$settings['cache']['default'] = 'cache.backend.redis';
$settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
