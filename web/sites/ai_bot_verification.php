<?php

/**
 * @file
 * IP-range verification for AI crawlers (opt-in, no-WAF fallback).
 *
 * robots.txt is advisory and any client can spoof "User-agent: GPTBot". When a
 * request claims a known AI bot, this snippet checks its source IP against the
 * ranges that bot's vendor publishes; a claim from outside them gets a 403.
 * Ranges come from a local cache refreshed out-of-band (see
 * \Drupal\server_general\Hook\AiBotRangeCronHook), so verification is a pure
 * in-memory match with no per-request network I/O.
 *
 * Fails open: a missing, unreadable, or incomplete cache leaves the request
 * untouched rather than blocking a real crawler.
 *
 * Not enabled by default. Require it from settings, next to
 * bot_trap_protection.php (e.g. web/sites/default/settings.pantheon.php):
 * @code
 * require __DIR__ . '/../ai_bot_verification.php';
 * @endcode
 * That also arms the cron refresh; seed once with drush refresh-ai-bot-ranges.
 */

// Cache of published bot IP ranges, written by the cron refresh. Defining this
// constant also signals the cron hook that the feature is enabled.
if (!defined('AI_BOT_VERIFICATION_CACHE_FILE')) {
  define('AI_BOT_VERIFICATION_CACHE_FILE', __DIR__ . '/default/files/ai_bot_verification_ranges.json');
}

$request_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

if ($request_user_agent === '' || $remote_addr === '') {
  return;
}

// Fail open on any cache problem: better to skip verification than to block a
// real crawler over a missing or stale cache.
if (!is_readable(AI_BOT_VERIFICATION_CACHE_FILE)) {
  return;
}
$cache = json_decode((string) file_get_contents(AI_BOT_VERIFICATION_CACHE_FILE), TRUE);
if (!is_array($cache) || empty($cache['bots']) || !is_array($cache['bots'])) {
  return;
}

// Which known bot does this request claim to be? Tokens are matched in cache
// order (most-specific-first) so a broad token can't shadow a specific one.
$claimed_ranges = NULL;
foreach ($cache['bots'] as $token => $ranges) {
  if (is_array($ranges) && mb_stripos($request_user_agent, (string) $token) !== FALSE) {
    $claimed_ranges = $ranges;
    break;
  }
}

// Not an AI bot we verify, or no ranges on record for it — leave it untouched.
if (empty($claimed_ranges)) {
  return;
}

// Match the IP against the bot's CIDR ranges. inet_pton lets one binary-prefix
// compare cover both IPv4 and IPv6.
$ip_in_cidr = static function (string $ip, string $cidr): bool {
  if (strpos($cidr, '/') === FALSE) {
    return FALSE;
  }
  [$subnet, $bits] = explode('/', $cidr, 2);
  $ip_bin = @inet_pton($ip);
  $subnet_bin = @inet_pton($subnet);
  // Bail on malformed input or a mixed IPv4/IPv6 comparison (differing length).
  if ($ip_bin === FALSE || $subnet_bin === FALSE || strlen($ip_bin) !== strlen($subnet_bin)) {
    return FALSE;
  }
  $bits = (int) $bits;
  $whole_bytes = intdiv($bits, 8);
  if ($whole_bytes > 0 && strncmp($ip_bin, $subnet_bin, $whole_bytes) !== 0) {
    return FALSE;
  }
  $remaining_bits = $bits % 8;
  if ($remaining_bits === 0) {
    return TRUE;
  }
  $mask = chr((0xFF << (8 - $remaining_bits)) & 0xFF);
  return ($ip_bin[$whole_bytes] & $mask) === ($subnet_bin[$whole_bytes] & $mask);
};

foreach ($claimed_ranges as $cidr) {
  if (is_string($cidr) && $ip_in_cidr($remote_addr, $cidr)) {
    // Verified: the claimed bot is coming from one of its published ranges.
    return;
  }
}

// Claims a known bot from an IP outside its published ranges — an impersonator.
header('HTTP/1.0 403 Forbidden');
exit;
