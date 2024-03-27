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
 * Locked Response Exception.
 *
 * Exception used for when a response is attempted to be modified while its
 * locked.
 */
class LockedResponseException extends \RuntimeException implements KleinExceptionInterface {

}
