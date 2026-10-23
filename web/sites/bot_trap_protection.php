<?php

/**
 * @file
 * Bot trap mitigation for facets.
 *
 * Some bots exploit query strings used in facets, such as "f[]", which are
 * commonly used in Drupal's Views and facet systems to manipulate filters.
 * These bots can lead to excessive
 * system load. This snippet detects such patterns
 * and blocks them by sending a 403 Forbidden response.
 *
 * @see https://acquia.my.site.com/s/article/How-do-I-manage-an-application-that-receives-lots-of-requests-for-faceted-searches
 */

$request_uri_query = $_SERVER['QUERY_STRING'] ?? '';
$request_user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

// Define the patterns to search for.
$query_patterns = [
  // URL encoded form of 'f['.
  'f%5B',
  // Double URL encoded form of 'f['. Bots seem to use it.
  'f%255b',
  'f[',
];
$user_agent_patterns = ['spider', 'bot', 'crawler', 'netestate'];

foreach ($query_patterns as $query_pattern) {
  // Check for the query pattern in the request URI.
  if (mb_stripos($request_uri_query, $query_pattern) === FALSE) {
    continue;
  }

  // Check for user agent patterns.
  foreach ($user_agent_patterns as $pattern) {
    if (mb_stripos($request_user_agent, $pattern) === FALSE) {
      continue;
    }
    header('HTTP/1.0 403 Forbidden');
    exit;
  }
}
