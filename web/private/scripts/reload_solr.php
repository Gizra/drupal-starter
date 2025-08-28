<?php
passthru('drush en search_api_solr_admin -y');
passthru('drush solr-reload pantheon_solr8');
