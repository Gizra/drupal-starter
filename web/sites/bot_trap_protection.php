<?php

/**
 * @file
 * Bot trap mitigation for facets.
 *
 * Bots crawling facet URLs, such as "type[]" of the search view, overload the
 * site. Such requests from bots get a 403 Forbidden response.
 *
 * @see https://acquia.my.site.com/s/article/How-do-I-manage-an-application-that-receives-lots-of-requests-for-faceted-searches
 */

$request_uri_query = $_SERVER['QUERY_STRING'] ?? '';
$request_user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

// Define the patterns to search for.
$query_patterns = [
  // URL encoded form of 'type['.
  'type%5B',
  // Double URL encoded form of 'type['. Bots seem to use it.
  'type%255b',
  'type[',
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
