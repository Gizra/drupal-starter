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

// UNDCO <-> UNSDG content sharing.
$settings['unsdg_content_sync.domain'] = $_ENV['docker_production_unsdg_content_domain'] ?: 'https://unsdg.un.org';
$settings['unsdg_content_sync.jsonapi_user'] = $_ENV['docker_production_unsdg_content_domain'] ?: 'jsonapi.client';
$settings['unsdg_content_sync.jsonapi_pass'] = $_ENV['docker_production_unsdg_content_sync_password'] ?: '1234';

// Custom settings.
$settings['file_private_path'] = '/app/private';
$settings['config_sync_directory'] = '../config/sync';

// Redis Config
$settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
$settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
$settings['redis.connection']['interface'] = 'PhpRedis'; // Can be "Predis".
$settings['redis.connection']['host']      = $_ENV['docker_production_redis_host'] ?: 'undco-redis';
$settings['cache']['default'] = 'cache.backend.redis';

$settings['trusted_host_patterns'] = [
  'localhost',
  '^www\.un-dco\.org$',
];
