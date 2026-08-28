<?php

/**
 * @file
 * Forward-confirmed reverse DNS verification for AI crawlers.
 *
 * robots.txt only asks well-behaved bots to follow a policy, and any client
 * can send a "User-agent: GPTBot" header. This snippet enforces bot identity:
 * when a request CLAIMS to be one of the known AI bots, its source IP must
 * reverse-resolve to the vendor's domain, and that hostname must
 * forward-resolve back to the same IP (forward-confirmed reverse DNS).
 * Requests that fail verification are impersonators, and get a 403.
 *
 * Requests whose user agent does not match a known AI bot are left untouched,
 * so ordinary traffic never triggers a DNS lookup — only requests claiming to
 * be an AI bot pay the cost.
 *
 * This is a lightweight, no-WAF fallback. When a CDN/WAF is available, prefer
 * its verified-bot rules (e.g. Cloudflare) over this snippet.
 *
 * Not enabled by default. To turn it on, require this file from your settings,
 * next to bot_trap_protection.php, e.g. in
 * web/sites/default/settings.pantheon.php:
 * @code
 * require __DIR__ . '/../ai_bot_verification.php';
 * @endcode
 */

$request_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

if ($request_user_agent === '' || $remote_addr === '') {
  return;
}

// Map each verifiable AI bot's user-agent token to the DNS domain suffixes its
// crawlers reverse-resolve to. Sources are the vendor bot docs linked from
// web/robots.txt. Order matters: the more specific token must come first so a
// broader one does not shadow it (e.g. "Claude-User" before "ClaudeBot").
$verified_bots = [
  'GPTBot' => ['.openai.com'],
  'OAI-SearchBot' => ['.openai.com'],
  'ChatGPT-User' => ['.openai.com'],
  'Claude-SearchBot' => ['.anthropic.com'],
  'Claude-User' => ['.anthropic.com'],
  'ClaudeBot' => ['.anthropic.com'],
  'PerplexityBot' => ['.perplexity.ai'],
  'Perplexity-User' => ['.perplexity.ai'],
  'Meta-ExternalAgent' => ['.facebook.com'],
];

// Find which known bot, if any, this request claims to be.
$claimed_suffixes = NULL;
foreach ($verified_bots as $token => $suffixes) {
  if (mb_stripos($request_user_agent, $token) !== FALSE) {
    $claimed_suffixes = $suffixes;
    break;
  }
}

// Not an AI bot we verify — leave the request alone.
if ($claimed_suffixes === NULL) {
  return;
}

// Step 1: reverse DNS. The hostname must end in a vendor suffix. Prefixing the
// hostname with a dot forces the suffix to match on a DNS label boundary, so
// "notopenai.com" cannot pass as ".openai.com".
$hostname = gethostbyaddr($remote_addr);
$is_vendor_host = FALSE;
if ($hostname !== FALSE && $hostname !== $remote_addr) {
  foreach ($claimed_suffixes as $suffix) {
    if (str_ends_with('.' . $hostname, $suffix)) {
      $is_vendor_host = TRUE;
      break;
    }
  }
}

// Step 2: forward-confirm. The vendor hostname must resolve back to the exact
// IP that made the request, defeating spoofed or stale reverse-DNS records.
$forward_confirmed = FALSE;
if ($is_vendor_host) {
  $records = @dns_get_record($hostname, DNS_A | DNS_AAAA);
  if ($records !== FALSE) {
    foreach ($records as $record) {
      $resolved_ip = $record['ip'] ?? $record['ipv6'] ?? '';
      if ($resolved_ip === $remote_addr) {
        $forward_confirmed = TRUE;
        break;
      }
    }
  }
}

if (!$forward_confirmed) {
  header('HTTP/1.0 403 Forbidden');
  exit;
}
