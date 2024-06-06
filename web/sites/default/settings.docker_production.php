<?php

/**
 * @file
 * Custom settings for Docker Production.
 */

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/services.yml';

$databases['default']['default'] = [
  'database' => $_ENV['docker_production_database'],
  'username' => $_ENV['docker_production_username'],
  'password' => $_ENV['docker_production_password'],
  'host' => $_ENV['docker_production_host'],
  'driver' => "mysql",
  'port' => $_ENV['docker_production_port'],
  'prefix' => "",
];

$settings['hash_salt'] = 'ETXSRhodvuWLJsBUnpgkRpTXOLqbuozKXwjwZkuGiHSCpdEQLHXgdgGUHeCVHnXv';

// Custom settings.
$settings['file_private_path'] = '/app/private';
$settings['config_sync_directory'] = '../config/sync';

// Redis Config
$settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
$settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
$settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
$settings['redis.connection']['host']      = $_ENV['docker_production_redis_host'] ?: 'starter-redis';
$settings['cache']['default'] = 'cache.backend.redis';

$settings['trusted_host_patterns'] = [
  'localhost',
];
