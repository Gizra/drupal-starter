<?php

/**
 * @file
 * Reload Solr after DB clone on Pantheon.
 *
 * Quicksilver hook: runs after clone_database workflow.
 */

declare(strict_types=1);

echo "Enabling search_api_solr_admin module...\n";
passthru('drush en search_api_solr_admin -y', $enable_status);
if ($enable_status !== 0) {
  echo "Warning: Failed to enable search_api_solr_admin.\n";
}

echo "Reloading Solr core...\n";
passthru('drush solr-reload pantheon_solr8', $reload_status);
if ($reload_status !== 0) {
  echo "Warning: Failed to reload Solr.\n";
}
