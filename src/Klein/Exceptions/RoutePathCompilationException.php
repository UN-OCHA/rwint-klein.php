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

use Klein\Route;

/**
 * Route Path Compilation Exception.
 *
 * Exception used for when a route's path fails to compile.
 */
class RoutePathCompilationException extends \RuntimeException implements KleinExceptionInterface {

  /**
   * Constants.
   */

  /**
   * The exception message format.
   *
   * @type string
   */
  public const MESSAGE_FORMAT = 'Route failed to compile with path "%s".';

  /**
   * The extra failure message format.
   *
   * @type string
   */
  public const FAILURE_MESSAGE_TITLE_FORMAT = 'Failed with message: "%s"';


  /**
   * Properties.
   */

  /**
   * The route that failed to compile.
   *
   * @var \Klein\Route
   */
  protected Route $route;


  /**
   * Methods.
   */

  /**
   * Create an exception from a route and an optional previous exception.
   *
   * @param \Klein\Route $route
   *   The route that failed to compile.
   * @param \Throwable $previous
   *   The previous exception.
   *
   * @return \Klein\Exceptions\RoutePathCompilationException
   *   The route path compilation exception.
   *
   * @todo Change the `$previous` parameter to type-hint against `Throwable`
   * once PHP 5.x support is no longer necessary.
   */
  public static function createFromRoute(Route $route, ?\Throwable $previous = NULL): RoutePathCompilationException {
    $error = (NULL !== $previous) ? $previous->getMessage() : NULL;
    $code = (NULL !== $previous) ? $previous->getCode() : 0;

    $message = sprintf(static::MESSAGE_FORMAT, $route->getPath());
    $message .= ' ' . sprintf(static::FAILURE_MESSAGE_TITLE_FORMAT, $error);

    $exception = new static($message, $code, $previous);
    $exception->setRoute($route);

    return $exception;
  }

  /**
   * Gets the value of route.
   *
   * @return \Klein\Route
   *   The route associated with this exception.
   */
  public function getRoute(): Route {
    return $this->route;
  }

  /**
   * Sets the value of route.
   *
   * @param \Klein\Route $route
   *   The route that failed to compile.
   *
   * @return static
   *   This object.
   */
  protected function setRoute(Route $route): static {
    $this->route = $route;

    return $this;
  }

}
