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
 * Klein Exception Interface.
 *
 * Exception interface that Klein's exceptions should implement.
 *
 * This is mostly for having a simple, common Interface class/namespace
 * that can be type-hinted/instance-checked against, therefore making it
 * easier to handle Klein exceptions while still allowing the different
 * exception classes to properly extend the corresponding SPL Exception type.
 */
interface KleinExceptionInterface extends \Throwable {

  /**
   * Constructor.
   */
  public function __construct();

}
