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

use Klein\Route;

/**
 * Route Collection.
 *
 * A Data Collection for Routes.
 */
class RouteCollection extends DataCollection {

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param callable[]|\Klein\Route[] $routes
   *   The routes of this collection.
   *
   * @override (doesn't call our parent).
   */
  public function __construct(array $routes = []) {
    foreach ($routes as $value) {
      $this->add($value);
    }
  }

  /**
   * Set a route.
   *
   * A value may either be a callable or a Route instance
   * Callable values will be converted into a Route with
   * the "name" of the route being set from the "key"
   *
   * A developer may add a named route to the collection
   * by passing the name of the route as the "$key" and an
   * instance of a Route as the "$value"
   *
   * {@inheritdoc}
   */
  public function set(string|int $key, mixed $value): static {
    if (!$value instanceof Route) {
      if (is_callable($value)) {
        $value = new Route($value);
      }
      else {
        throw new \RuntimeException('Invalid route callable');
      }
    }

    return parent::set($key, $value);
  }

  /**
   * Add a route instance to the collection.
   *
   * This will auto-generate a name.
   *
   * @param \Klein\Route $route
   *   Route.
   *
   * @return static
   *   This object.
   */
  public function addRoute(Route $route): static {
    // Auto-generate a name from the object's hash.
    // This makes it so that we can autogenerate names that ensure duplicate
    // route instances are overridden.
    $name = spl_object_hash($route);

    return $this->set($name, $route);
  }

  /**
   * Add a route to the collection.
   *
   * This allows a more generic form that will take a Route instance, string
   * callable or any other Route class compatible callback.
   *
   * @param \Klein\Route|callable $route
   *   Route object or callable that returns a route.
   *
   * @return static
   *   This object.
   */
  public function add(Route|callable $route): static {
    if (!$route instanceof Route) {
      $route = new Route($route);
    }

    return $this->addRoute($route);
  }

  /**
   * Prepare the named routes in the collection.
   *
   * This loops through every route to set the collection's key name for that
   * route to equal the routes name, if its changed.
   *
   * Thankfully, because routes are all objects, this doesn't
   * take much memory as its simply moving references around.
   *
   * @return static
   *   This object.
   */
  public function prepareNamed(): static {
    // Create a new collection so we can keep our order.
    $prepared = new static();

    foreach ($this as $route) {
      if ($route instanceof Route) {
        $route_name = $route->getName();

        if (!empty($route_name)) {
          // Add the route to the new set with the new name.
          $prepared->set($route_name, $route);
        }
        else {
          $prepared->add($route);
        }
      }
    }

    // Replace our collection's items with our newly prepared collection's
    // items.
    $this->replace($prepared->all());

    return $this;
  }

}
