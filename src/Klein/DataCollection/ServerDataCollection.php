<?php

declare(strict_types=1);

// phpcs:disable
/**
 * Klein (klein.php) - A fast & flexible router for PHP
 *
 * @author Chris O'Hara <cohara87@gmail.com>
 * @author Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright (c) Chris O'Hara
 * @link https://github.com/klein/klein.php
 * @license MIT
 */
// phpcs:enable

namespace Klein\DataCollection;

/**
 * ServerDataCollection.
 *
 * A DataCollection for "$_SERVER" like data.
 *
 * Look familiar?
 *
 * Inspired by @fabpot's Symfony 2's HttpFoundation
 *
 * @link https://github.com/symfony/HttpFoundation/blob/master/ServerBag.php
 */
class ServerDataCollection extends DataCollection {

  /**
   * Class properties.
   */

  /**
   * The prefix of HTTP headers normally stored in the Server data.
   *
   * @var string
   */
  protected static string $httpHeaderPrefix = 'HTTP_';

  /**
   * The list of HTTP headers that for some reason aren't prefixed in PHP...
   *
   * @var string[]
   */
  protected static array $httpNonprefixedHeaders = [
    'CONTENT_LENGTH',
    'CONTENT_TYPE',
    'CONTENT_MD5',
  ];


  /**
   * Methods.
   */

  /**
   * Quickly check if a string has a passed prefix.
   *
   * @param string $string
   *   The string to check.
   * @param string $prefix
   *   The prefix to test.
   *
   * @return bool
   *   TRUE if has prefix.
   */
  public static function hasPrefix(string $string, string $prefix): bool {
    if (strpos($string, $prefix) === 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get our headers from our server data collection.
   *
   * PHP is weird... it puts all of the HTTP request
   * headers in the $_SERVER array. This handles that.
   *
   * @return array<string|int, mixed>
   *   Headers.
   */
  public function getHeaders(): array {
    // Define a headers array.
    $headers = [];

    foreach ($this->attributes as $key => $value) {
      $key = (string) $key;
      // Does our server attribute have our header prefix?
      if (self::hasPrefix((string) $key, self::$httpHeaderPrefix)) {
        // Add our server attribute to our header array.
        $headers[substr($key, strlen(self::$httpHeaderPrefix))] = $value;
      }

      elseif (in_array($key, self::$httpNonprefixedHeaders)) {
        // Add our server attribute to our header array.
        $headers[$key] = $value;
      }
    }

    return $headers;
  }

}
