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

namespace Klein;

/**
 * RouteFactory.
 *
 * The default implementation of the AbstractRouteFactory.
 */
class RouteFactory extends AbstractRouteFactory {

  /**
   * Constants.
   */

  /**
   * The value given to path's when they are entered as null values.
   *
   * @type string
   */
  const NULL_PATH_VALUE = '*';


  /**
   * Methods.
   */

  /**
   * Check if the path is null or equal to our match-all, null-like value.
   *
   * @param ?string $path
   *   Path.
   *
   * @return bool
   *   TRUE if the path is null.
   */
  protected function pathIsNull(?string $path = NULL): bool {
    return (static::NULL_PATH_VALUE === $path || NULL === $path);
  }

  /**
   * Check if the path should count as a route match.
   *
   * Quick check to see whether or not to count the route as a match when
   * counting total matches.
   *
   * @param ?string $path
   *   Path.
   *
   * @return bool
   *   TRUE if it should match.
   */
  protected function shouldPathStringCauseRouteMatch(?string $path = NULL): bool {
    // Only consider a request to be matched when not using 'matchall'.
    return !$this->pathIsNull($path);
  }

  /**
   * Pre-process a path string.
   *
   * This method wraps the path string in a regular expression syntax baesd
   * on whether the string is a catch-all or custom regular expression.
   * It also adds the namespace in a specific part, based on the style of
   * expression.
   *
   * @param ?string $path
   *   Path.
   *
   * @return string
   *   Preprocessed path.
   */
  protected function preprocessPathString(?string $path = NULL): string {
    // If the path is null, make sure to give it our match-all value.
    $path = (NULL === $path) ? static::NULL_PATH_VALUE : (string) $path;

    // If a custom regular expression (or negated custom regex).
    if ($this->namespace
      && (isset($path[0]) && $path[0] === '@')
      || (isset($path[0]) && $path[0] === '!' && isset($path[1]) && $path[1] === '@')
    ) {
      // Is it negated?
      if ($path[0] === '!') {
        $negate = TRUE;
        $path = substr($path, 2);
      }
      else {
        $negate = FALSE;
        $path = substr($path, 1);
      }

      // Regex anchored to front of string.
      if ($path[0] === '^') {
        $path = substr($path, 1);
      }
      else {
        $path = '.*' . $path;
      }

      if ($negate) {
        $path = '@^' . $this->namespace . '(?!' . $path . ')';
      }
      else {
        $path = '@^' . $this->namespace . $path;
      }

    }

    elseif ($this->namespace && $this->pathIsNull($path)) {
      // Empty route with namespace is a match-all.
      $path = '@^' . $this->namespace . '(/|$)';
    }
    else {
      // Just prepend our namespace.
      $path = $this->namespace . $path;
    }

    return $path;
  }

  /**
   * Build a Route instance.
   *
   * @param callable $callback
   *   Callable callback method to execute on route match.
   * @param ?string $path
   *   Route URI path to match.
   * @param string|array<string>|null $method
   *   HTTP Method to match.
   * @param ?bool $count_match
   *   Whether or not to count the route as a match when counting total matches.
   * @param ?string $name
   *   The name of the route.
   *
   * @return \Klein\Route
   *   Route.
   */
  public function build(
    callable $callback,
    ?string $path = NULL,
    string|array|null $method = NULL,
    ?bool $count_match = TRUE,
    ?string $name = NULL
  ): Route {
    return new Route(
      $callback,
      $this->preprocessPathString($path),
      $method,
      // Ignore the $count_match boolean that they passed.
      $this->shouldPathStringCauseRouteMatch($path)
    );
  }

}
