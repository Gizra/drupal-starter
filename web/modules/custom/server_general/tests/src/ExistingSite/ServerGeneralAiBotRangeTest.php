<?php

declare(strict_types=1);

namespace Drupal\Tests\server_general\ExistingSite;

use Drupal\server_general\Service\AiBotRangeUpdater;

/**
 * Tests the AI-bot IP-range verification building blocks.
 *
 * Covers the pure logic reused by web/sites/ai_bot_verification.php: parsing a
 * vendor payload into CIDR ranges, and matching an IP against a range. Also
 * exercises the refresh service end-to-end (network permitting).
 */
class ServerGeneralAiBotRangeTest extends ServerGeneralTestBase {

  /**
   * Tests parsing vendor payloads into a flat list of CIDR ranges.
   */
  public function testParsePrefixes(): void {
    $payload = [
      'creationTime' => '2026-01-01T00:00:00.0000000Z',
      'prefixes' => [
        ['ipv4Prefix' => '1.2.3.0/24'],
        ['ipv6Prefix' => '2001:db8::/32'],
        // Malformed / empty entries must be skipped.
        ['ipv4Prefix' => ''],
        ['note' => 'no prefix here'],
        'not-an-array',
      ],
    ];
    $this->assertSame(
      ['1.2.3.0/24', '2001:db8::/32'],
      AiBotRangeUpdater::parsePrefixes($payload)
    );

    // Missing "prefixes" key yields an empty list, never a warning.
    $this->assertSame([], AiBotRangeUpdater::parsePrefixes([]));
  }

  /**
   * Tests IPv4 and IPv6 CIDR matching, including boundaries and mixed families.
   *
   * @dataProvider providerIpInRange
   */
  public function testIpInRange(bool $expected, string $ip, string $cidr): void {
    $this->assertSame($expected, AiBotRangeUpdater::ipInRange($ip, $cidr));
  }

  /**
   * Data provider for testIpInRange().
   *
   * @return array[]
   *   Each case is [expected, ip, cidr].
   */
  public static function providerIpInRange(): array {
    return [
      'ipv4 inside /24' => [TRUE, '1.2.3.42', '1.2.3.0/24'],
      'ipv4 outside /24' => [FALSE, '1.2.4.42', '1.2.3.0/24'],
      'ipv4 first of block' => [TRUE, '1.2.3.0', '1.2.3.0/24'],
      'ipv4 last of block' => [TRUE, '1.2.3.255', '1.2.3.0/24'],
      'ipv4 /32 exact' => [TRUE, '9.9.9.9', '9.9.9.9/32'],
      'ipv4 /32 mismatch' => [FALSE, '9.9.9.8', '9.9.9.9/32'],
      'ipv4 non-byte boundary /25 in' => [TRUE, '10.0.0.5', '10.0.0.0/25'],
      'ipv4 non-byte boundary /25 out' => [FALSE, '10.0.0.200', '10.0.0.0/25'],
      'ipv6 inside /32' => [TRUE, '2001:db8::1', '2001:db8::/32'],
      'ipv6 outside /32' => [FALSE, '2001:db9::1', '2001:db8::/32'],
      'mixed families never match' => [FALSE, '1.2.3.4', '2001:db8::/32'],
      'no slash is rejected' => [FALSE, '1.2.3.4', '1.2.3.4'],
      'garbage ip is rejected' => [FALSE, 'not-an-ip', '1.2.3.0/24'],
    ];
  }

  /**
   * Tests that refresh() writes a well-formed cache file.
   *
   * Network access to the vendor endpoints is best-effort here: if a fetch
   * fails the token is simply omitted, but the cache file must still be written
   * with a valid structure, which is what the pre-bootstrap snippet relies on.
   */
  public function testRefreshWritesCache(): void {
    /** @var \Drupal\server_general\Service\AiBotRangeUpdater $updater */
    $updater = \Drupal::service('server_general.ai_bot_range_updater');
    $updater->refresh();

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $path = $file_system->realpath(AiBotRangeUpdater::CACHE_URI);
    $this->assertNotFalse($path);
    $this->assertFileExists($path);

    $cache = json_decode((string) file_get_contents($path), TRUE);
    $this->assertIsArray($cache);
    $this->assertArrayHasKey('generated', $cache);
    $this->assertArrayHasKey('bots', $cache);
    $this->assertIsArray($cache['bots']);
  }

}
