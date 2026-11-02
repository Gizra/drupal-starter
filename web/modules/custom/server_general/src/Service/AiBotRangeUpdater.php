<?php

declare(strict_types=1);

namespace Drupal\server_general\Service;

use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches vendor AI-bot IP-range lists and caches them for verification.
 *
 * Consumed pre-bootstrap by web/sites/ai_bot_verification.php, which matches a
 * request's IP against these ranges with no per-request network I/O. Fetching
 * happens here, out-of-band (cron / drush). Vendors publish ranges in a shared
 * "Google-style" format: {"prefixes": [{"ipv4Prefix": "1.2.3.0/24"}, ...]}.
 */
class AiBotRangeUpdater {

  /**
   * Cache location.
   */
  const CACHE_URI = 'public://ai_bot_verification_ranges.json';

  /**
   * Minimum age, in seconds, before refreshIfStale() re-fetches (6 hours).
   */
  const REFRESH_INTERVAL = 21600;

  /**
   * Timeout, in seconds, for each vendor request (connect and total).
   */
  const REQUEST_TIMEOUT = 8;

  /**
   * Maps each verifiable bot's user-agent token to its published range list.
   *
   * Order is preserved in the cache and matched most-specific-first, so a broad
   * token can't shadow a specific one (e.g. "Claude-User" before "ClaudeBot");
   * the Claude bots share one Anthropic list. Meta-ExternalAgent is omitted:
   * Meta publishes no list, so those requests can't be verified and are left
   * alone.
   */
  const SOURCES = [
    'ChatGPT-User' => 'https://openai.com/chatgpt-user.json',
    'OAI-SearchBot' => 'https://openai.com/searchbot.json',
    'GPTBot' => 'https://openai.com/gptbot.json',
    'Claude-SearchBot' => 'https://claude.com/crawling/bots.json',
    'Claude-User' => 'https://claude.com/crawling/bots.json',
    'ClaudeBot' => 'https://claude.com/crawling/bots.json',
    'Perplexity-User' => 'https://www.perplexity.ai/perplexity-user.json',
    'PerplexityBot' => 'https://www.perplexity.ai/perplexitybot.json',
  ];

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an AiBotRangeUpdater object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(ClientInterface $http_client, FileSystemInterface $file_system, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
  }

  /**
   * Refreshes only when the cache is missing or older than REFRESH_INTERVAL.
   */
  public function refreshIfStale(): void {
    $path = $this->fileSystem->realpath(self::CACHE_URI);
    if ($path !== FALSE && file_exists($path) && (time() - filemtime($path)) < self::REFRESH_INTERVAL) {
      return;
    }
    $this->refresh();
  }

  /**
   * Fetches every vendor list and writes the merged cache.
   *
   * A vendor that fails to fetch keeps its last-known ranges, so a transient
   * outage never empties a valid list.
   *
   * @return int
   *   Number of tokens written with at least one range.
   */
  public function refresh(): int {
    $previous = $this->readCache();

    // Fetch each distinct URL once; several tokens may share a list.
    $ranges_by_url = [];
    foreach (array_unique(array_values(self::SOURCES)) as $url) {
      $ranges = $this->fetchRanges($url);
      if ($ranges !== NULL) {
        $ranges_by_url[$url] = $ranges;
      }
    }

    $bots = [];
    foreach (self::SOURCES as $token => $url) {
      if (isset($ranges_by_url[$url])) {
        $bots[$token] = $ranges_by_url[$url];
        continue;
      }
      // Fetch failed: retain the last known-good ranges for this token.
      if (!empty($previous[$token])) {
        $bots[$token] = $previous[$token];
        $this->logger->warning('AI-bot range refresh: kept cached ranges for @token after a failed fetch of @url.', [
          '@token' => $token,
          '@url' => $url,
        ]);
      }
    }

    $payload = [
      'generated' => time(),
      'bots' => $bots,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $this->writeCacheAtomically($json);

    $this->logger->info('AI-bot range cache written with @count verifiable user-agent tokens.', [
      '@count' => count($bots),
    ]);

    return count($bots);
  }

  /**
   * Writes the cache atomically so a reader never sees a partial file.
   *
   * Writes a temp file in the same directory and rename()s it over the target;
   * a same-filesystem rename is atomic. FileSystem::saveData() can't guarantee
   * this because its temp lives on temporary://, often a different mount than
   * public:// (e.g. Pantheon), downgrading the move to a non-atomic copy().
   *
   * @param string $json
   *   The cache contents to write.
   */
  protected function writeCacheAtomically(string $json): void {
    $directory = $this->fileSystem->realpath('public://');
    $destination = $directory . '/' . basename(self::CACHE_URI);
    $temp = $destination . '.' . uniqid('tmp', TRUE);

    if (file_put_contents($temp, $json) === FALSE || !@rename($temp, $destination)) {
      @unlink($temp);
      throw new \RuntimeException(sprintf('Could not write the AI-bot range cache to %s.', $destination));
    }
    $this->fileSystem->chmod($destination);
  }

  /**
   * Fetches and parses one vendor list.
   *
   * @param string $url
   *   The vendor JSON URL.
   *
   * @return string[]|null
   *   The CIDR ranges, or NULL if the request or parsing failed.
   */
  protected function fetchRanges(string $url): ?array {
    try {
      $response = $this->httpClient->request('GET', $url, [
        'connect_timeout' => self::REQUEST_TIMEOUT,
        'timeout' => self::REQUEST_TIMEOUT,
      ]);
      $data = json_decode((string) $response->getBody(), TRUE);
      if (!is_array($data)) {
        throw new \RuntimeException('Response was not valid JSON.');
      }
      return self::parsePrefixes($data);
    }
    catch (\Throwable $e) {
      $this->logger->warning('AI-bot range refresh: could not fetch @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Extracts CIDR ranges from a decoded vendor payload.
   *
   * @param array $data
   *   The decoded JSON, expected to contain a "prefixes" list of entries with
   *   "ipv4Prefix" and/or "ipv6Prefix" keys.
   *
   * @return string[]
   *   The CIDR ranges found, in document order.
   */
  public static function parsePrefixes(array $data): array {
    $ranges = [];
    foreach ($data['prefixes'] ?? [] as $prefix) {
      if (!is_array($prefix)) {
        continue;
      }
      foreach (['ipv4Prefix', 'ipv6Prefix'] as $key) {
        if (!empty($prefix[$key]) && is_string($prefix[$key])) {
          $ranges[] = $prefix[$key];
        }
      }
    }
    return $ranges;
  }

  /**
   * Checks whether an IP falls within a CIDR range (IPv4 or IPv6).
   *
   * Canonical implementation; ai_bot_verification.php mirrors it because
   * pre-bootstrap code can't autoload this class.
   *
   * @param string $ip
   *   The IP address to test.
   * @param string $cidr
   *   The CIDR range, e.g. "1.2.3.0/24" or "2001:db8::/32".
   *
   * @return bool
   *   TRUE when the IP is inside the range.
   */
  public static function ipInRange(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === FALSE) {
      return FALSE;
    }
    [$subnet, $bits] = explode('/', $cidr, 2);
    $ip_bin = @inet_pton($ip);
    $subnet_bin = @inet_pton($subnet);
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
  }

  /**
   * Reads the current cache file's token-to-ranges map.
   *
   * @return array
   *   The "bots" map, or an empty array when no valid cache exists yet.
   */
  protected function readCache(): array {
    $path = $this->fileSystem->realpath(self::CACHE_URI);
    if ($path === FALSE || !is_readable($path)) {
      return [];
    }
    $data = json_decode((string) file_get_contents($path), TRUE);
    return is_array($data) && isset($data['bots']) && is_array($data['bots']) ? $data['bots'] : [];
  }

}
