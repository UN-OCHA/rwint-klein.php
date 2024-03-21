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

use Klein\ResponseCookie;

/**
 * Response Cookie Data Collection.
 *
 * A DataCollection for HTTP response cookies.
 */
class ResponseCookieDataCollection extends DataCollection {

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param array<string|int, mixed> $cookies
   *   The cookies of this collection.
   *
   * @override (doesn't call our parent).
   */
  public function __construct(array $cookies = []) {
    foreach ($cookies as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * Set a cookie.
   *
   * A value may either be a string or a ResponseCookie instance
   * String values will be converted into a ResponseCookie with
   * the "name" of the cookie being set from the "key"
   *
   * Obviously, the developer is free to organize this collection
   * however they like, and can be more explicit by passing a more
   * suggested "$key" as the cookie's "domain" and passing in an
   * instance of a ResponseCookie as the "$value"
   *
   * {@inheritdoc}
   */
  public function set(string|int $key, mixed $value): static {
    if (!$value instanceof ResponseCookie) {
      if (is_string($value)) {
        $value = new ResponseCookie((string) $key, $value);
      }
      else {
        throw new \RuntimeException('Invalid cookie value');
      }
    }

    return parent::set($key, $value);
  }

}
