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

namespace Klein\Exceptions;

/**
 * Http Exception.
 *
 * An HTTP error exception.
 */
class HttpException extends \RuntimeException implements HttpExceptionInterface {

  /**
   * Methods.
   */

  /**
   * Create an HTTP exception from nothing but an HTTP code.
   *
   * @param int $code
   *   Status code.
   *
   * @return \Klein\Exceptions\HttpExceptionInterface
   *   This object.
   */
  public static function createFromCode(int $code): HttpExceptionInterface {
    return new static('', (int) $code);
  }

}
