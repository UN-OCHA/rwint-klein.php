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

use Klein\Exceptions\DuplicateServiceException;
use Klein\Exceptions\UnknownServiceException;

/**
 * App.
 *
 * App that handles services.
 */
class App {

  /**
   * Class properties.
   */

  /**
   * The array of app services.
   *
   * @var array<string, callable>
   */
  protected array $services = [];

  /**
   * Magic "__get" method.
   *
   * Allows the ability to arbitrarily request a service from this instance
   * while treating it as an instance property.
   *
   * This checks the lazy service register and automatically calls the
   * registered service method.
   *
   * @param string $name
   *   The name of the service.
   *
   * @return mixed
   *   The service matching the name.
   *
   * @throws \Klein\Exceptions\UnknownServiceException
   *   If a non-registered service is attempted to fetched.
   */
  public function __get(string $name): mixed {
    if (!isset($this->services[$name])) {
      throw new UnknownServiceException('Unknown service ' . $name);
    }
    $service = $this->services[$name];

    return $service();
  }

  /**
   * Magic "__call" method.
   *
   * Allows the ability to arbitrarily call a property as a callable method.
   *
   * Allows callbacks to be assigned as properties and called like normal
   * methods.
   *
   * @param string $method
   *   The callable method to execute.
   * @param mixed[] $args
   *   The argument array to pass to our callback.
   *
   * @return mixed
   *   Result of the service callback.
   *
   * @throws \BadMethodCallException
   *   If a non-registered method is attempted to be called.
   */
  public function __call(string $method, array $args): mixed {
    if (isset($this->services[$method]) && is_callable($this->services[$method])) {
      return call_user_func_array($this->services[$method], $args);
    }
    throw new \BadMethodCallException('Unknown method ' . $method . '()');
  }

  /**
   * Register a lazy service.
   *
   * @param string $name
   *   The name of the service.
   * @param callable $closure
   *   The callable function to execute when requesting our service.
   *
   * @throws \Klein\Exceptions\DuplicateServiceException
   *   If an attempt is made to register two services with the same name.
   */
  public function register(string $name, callable $closure): void {
    if (isset($this->services[$name])) {
      throw new DuplicateServiceException('A service is already registered under ' . $name);
    }

    $this->services[$name] = function () use ($closure) {
      static $instance;
      if (NULL === $instance) {
        $instance = $closure();
      }

      return $instance;
    };
  }

}
