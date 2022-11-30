<?php

declare(strict_types=1);

namespace Drupal\server_general;

/**
 * Helper class to convert Arabic numbers to English or vice versa.
 */
class ArabicNumberConverter {

  const NUMBER_MAPPING = [
    '0' => '٠',
    '1' => '١',
    '2' => '٢',
    '3' => '٣',
    '4' => '٤',
    '5' => '٥',
    '6' => '٦',
    '7' => '٧',
    '8' => '٨',
    '9' => '٩',
  ];

  /**
   * Convert English numbers to Arabic.
   *
   * @param string $string
   *   The string containing the numbers.
   *
   * @return string
   *   The string with English numbers converted to Arabic.
   */
  public static function enToAr(string $string): string {
    return strtr($string, self::NUMBER_MAPPING);
  }

  /**
   * Convert Arabic numbers to English.
   *
   * @param string $string
   *   The string containing the numbers.
   *
   * @return string
   *   The string with Arabic numbers converted to English.
   */
  public static function arToEn(string $string): string {
    return strtr($string, array_flip(self::NUMBER_MAPPING));
  }

}
