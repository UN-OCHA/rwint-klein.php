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
 * Abstract Route Factory.
 *
 * Abstract class for a factory for building new Route instances.
 */
abstract class AbstractRouteFactory {

  /**
   * Properties.
   */

  /**
   * Route namespace.
   *
   * The namespace of which to collect the routes in when matching, so you can
   * define routes under a common endpoint.
   *
   * @var string
   */
  protected string $namespace;

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param string $namespace
   *   The initial namespace to set.
   */
  public function __construct(string $namespace = '') {
    $this->namespace = $namespace;
  }

  /**
   * Gets the value of namespace.
   *
   * @return string
   *   Namespace.
   */
  public function getNamespace(): string {
    return $this->namespace;
  }

  /**
   * Sets the value of namespace.
   *
   * @param string $namespace
   *   The namespace from which to collect the Routes under.
   *
   * @return static
   *   This object.
   */
  public function setNamespace(string $namespace): static {
    $this->namespace = (string) $namespace;

    return $this;
  }

  /**
   * Append a namespace to the current namespace.
   *
   * @param string $namespace
   *   The namespace from which to collect the Routes under.
   *
   * @return static
   *   This object.
   */
  public function appendNamespace(string $namespace): static {
    $this->namespace .= (string) $namespace;

    return $this;
  }

  /**
   * Build factory method.
   *
   * This method should be implemented to return a Route instance.
   *
   * @param callable $callback
   *   Callable callback method to execute on route match.
   * @param ?string $path
   *   Route URI path to match.
   * @param string|array<string>|null $method
   *   HTTP Method to match. Array, string or NULL.
   * @param ?bool $count_match
   *   Whether or not to count the route as a match when counting total matches.
   * @param ?string $name
   *   The name of the route.
   *
   * @return \Klein\Route
   *   The route.
   */
  abstract public function build(
    callable $callback,
    ?string $path = NULL,
    string|array|null $method = NULL,
    ?bool $count_match = NULL,
    ?string $name = NULL,
  );

}
