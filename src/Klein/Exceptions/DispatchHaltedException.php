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
 * Dispatch Halted Exception.
 *
 * Exception used to halt a route callback from executing in a dispatch loop.
 */
class DispatchHaltedException extends \RuntimeException implements KleinExceptionInterface {

  /**
   * Constants.
   */

  /**
   * Skip this current match/callback.
   *
   * @type int
   */
  public const SKIP_THIS = 1;

  /**
   * Skip the next match/callback.
   *
   * @type int
   */
  public const SKIP_NEXT = 2;

  /**
   * Skip the rest of the matches.
   *
   * @type int
   */
  public const SKIP_REMAINING = 0;


  /**
   * Properties.
   */

  /**
   * The number of next matches to skip on a "next" skip.
   *
   * @var int
   */
  protected int $numberOfSkips = 1;

  /**
   * Methods.
   */

  /**
   * Gets the number of matches to skip on a "next" skip.
   *
   * @return int
   *   Number of matches to skip.
   */
  public function getNumberOfSkips(): int {
    return $this->numberOfSkips;
  }

  /**
   * Sets the number of matches to skip on a "next" skip.
   *
   * @param int $number_of_skips
   *   Number of matches to skip.
   *
   * @return static
   *   This object.
   */
  public function setNumberOfSkips(int $number_of_skips): static {
    $this->numberOfSkips = (int) $number_of_skips;

    return $this;
  }

}
