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
 * Route.
 *
 * Class to represent a route definition.
 */
class Route {

  /**
   * Properties.
   */

  /**
   * The callback method to execute when the route is matched.
   *
   * Any valid "callable" type is allowed.
   *
   * @var callable
   *
   * @link http://php.net/manual/en/language.types.callable.php
   */
  protected $callback;

  /**
   * The URL path to match.
   *
   * Allows for regular expression matching and/or basic string matching.
   *
   * Examples:
   * - '/posts'
   * - '/posts/[:post_slug]'
   * - '/posts/[i:id]'
   *
   * @var string
   */
  protected string $path;

  /**
   * The HTTP method to match.
   *
   * May either be represented as a string or an array containing multiple
   * methods to match.
   *
   * Examples:
   * - 'POST'
   * - array('GET', 'POST')
   *
   * @var string|array<string>|null
   */
  protected string|array|null $method;

  /**
   * Whether or not to count this route as a match when counting total matches.
   *
   * @var bool
   */
  protected bool $countMatch;

  /**
   * The name of the route.
   *
   * Mostly used for reverse routing.
   *
   * @var ?string
   */
  protected ?string $name;


  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param callable $callback
   *   Callback.
   * @param ?string $path
   *   Path.
   * @param string|array<string>|null $method
   *   Method.
   * @param ?bool $count_match
   *   Count match.
   * @param ?string $name
   *   Route name.
   */
  public function __construct(
    callable $callback,
    ?string $path = NULL,
    string|array|null $method = NULL,
    ?bool $count_match = TRUE,
    ?string $name = NULL,
  ) {
    // Initialize some properties (use our setters so we can validate param
    // types).
    $this->setCallback($callback);
    $this->setPath($path);
    $this->setMethod($method);
    $this->setCountMatch($count_match);
    $this->setName($name);
  }

  /**
   * Get the callback.
   *
   * @return callable
   *   Callback.
   */
  public function getCallback(): callable {
    return $this->callback;
  }

  /**
   * Set the callback.
   *
   * @param callable $callback
   *   Callback.
   *
   * @return static
   *   This object.
   *
   * @throws \InvalidArgumentException
   *   If the callback isn't a callable.
   */
  public function setCallback(callable $callback): static {
    $this->callback = $callback;

    return $this;
  }

  /**
   * Get the path.
   *
   * @return string
   *   Path.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Set the path.
   *
   * @param string $path
   *   Path.
   *
   * @return Route
   *   This object.
   */
  public function setPath(?string $path = NULL) {
    if (NULL !== $path) {
      $this->path = (string) $path;
    }
    else {
      $this->path = RouteFactory::NULL_PATH_VALUE;
    }

    return $this;
  }

  /**
   * Get the method.
   *
   * @return string|array<string>|null
   *   Method.
   */
  public function getMethod(): string|array|null {
    return $this->method;
  }

  /**
   * Set the method.
   *
   * @param mixed $method
   *   Method.
   *
   * @return static
   *   This object.
   *
   * @throws \InvalidArgumentException
   *   If a non-string or non-array type is passed.
   */
  public function setMethod(mixed $method): static {
    // Allow null, otherwise expect an array or a string.
    if (!is_null($method) && !is_array($method) && !is_string($method)) {
      throw new \InvalidArgumentException('Expected an array or string. Got a ' . gettype($method));
    }

    if ($method === NULL) {
      $this->method = NULL;
    }
    elseif (is_string($method)) {
      $this->method = $method;
    }
    else {
      $this->method = array_values(array_map(static function (mixed $m): string {
        return is_scalar($m) ? (string) $m : '';
      }, $method));
    }

    return $this;
  }

  /**
   * Get the count_match.
   *
   * @return bool
   *   Count match.
   */
  public function getCountMatch(): bool {
    return $this->countMatch;
  }

  /**
   * Set the count_match.
   *
   * @param ?bool $count_match
   *   Count match.
   *
   * @return static
   *   This object.
   */
  public function setCountMatch(?bool $count_match) {
    if (NULL !== $count_match) {
      $this->countMatch = (bool) $count_match;
    }
    else {
      $this->countMatch = FALSE;
    }

    return $this;
  }

  /**
   * Get the name.
   *
   * @return ?string
   *   Name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set the name.
   *
   * @param ?string $name
   *   Name.
   *
   * @return static
   *   This object.
   */
  public function setName(?string $name): static {
    if (NULL !== $name) {
      $this->name = (string) $name;
    }
    else {
      $this->name = $name;
    }

    return $this;
  }

  /**
   * Magic "__invoke" method.
   *
   * Allows the ability to arbitrarily call this instance like a function.
   *
   * @return mixed
   *   Result of called inner function.
   */
  public function __invoke(): mixed {
    $args = func_get_args();

    return call_user_func_array(
      $this->callback,
      $args
    );
  }

}
