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

namespace Klein\Tests\DataCollection;

use Klein\DataCollection\RouteCollection;
use Klein\Route;
use Klein\Tests\AbstractKleinTest;

/**
 * Route Collection Test.
 */
class RouteCollectionTest extends AbstractKleinTest {

  /**
   * Data Providers and Methods.
   */

  /**
   * Sample data provider.
   *
   * @return array
   *   Sample data.
   */
  public function sampleDataProvider() {
    $sample_route = new Route(
      function () {
        echo 'woot!';
      },
      '/test/path',
      'PUT',
      TRUE
    );

    $sample_other_route = new Route(
      function () {
        echo 'huh?';
      },
      '/test/dafuq',
      'HEAD',
      FALSE
    );

    $sample_named_route = new Route(
      function () {
        echo 'TREVOR!';
      },
      '/trevor/is/weird',
      'OPTIONS',
      FALSE,
      'trevor'
    );

    return [
      [$sample_route, $sample_other_route, $sample_named_route],
    ];
  }

  /**
   * Tests.
   */

  /**
   * Test set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testSet($sample_route, $sample_other_route) {
    // Create our collection with NO data.
    $routes = new RouteCollection();

    // Set our data from our test data.
    $routes->set('first', $sample_route);

    $this->assertSame($sample_route, $routes->get('first'));
    $this->assertTrue($routes->get('first') instanceof Route);
  }

  /**
   * Test set callable converts to route.
   */
  public function testSetCallableConvertsToRoute() {
    // Create our collection with NO data.
    $routes = new RouteCollection();

    // Set our data.
    $routes->set(
      'first',
      function () {
      }
    );

    $this->assertNotSame('value', $routes->get('first'));
    $this->assertTrue($routes->get('first') instanceof Route);
  }

  /**
   * Test constructor routes through add.
   *
   * @dataProvider sampleDataProvider
   */
  public function testConstructorRoutesThroughAdd($sample_route, $sample_other_route) {
    $array_of_route_instances = [
      $sample_route,
      $sample_other_route,
      new Route(
        function () {
        }
      ),
    ];

    // Create our collection.
    $routes = new RouteCollection($array_of_route_instances);
    $this->assertSame($array_of_route_instances, array_values($routes->all()));
    $this->assertNotSame(array_keys($array_of_route_instances), $routes->keys());

    foreach ($routes as $route) {
      $this->assertTrue($route instanceof Route);
    }
  }

  /**
   * Test add route.
   *
   * @dataProvider sampleDataProvider
   */
  public function testAddRoute($sample_route, $sample_other_route) {
    $array_of_routes = [
      $sample_route,
      $sample_other_route,
    ];

    // Create our collection.
    $routes = new RouteCollection();

    foreach ($array_of_routes as $route) {
      $routes->addRoute($route);
    }

    $this->assertSame($array_of_routes, array_values($routes->all()));
  }

  /**
   * Test add callable convderts to route.
   */
  public function testAddCallableConvertsToRoute() {
    // Create our collection with NO data.
    $routes = new RouteCollection();

    $callable = function () {
    };

    // Add our data.
    $routes->add($callable);

    $this->assertNotSame($callable, current($routes->all()));
    $this->assertTrue(current($routes->all()) instanceof Route);
  }

  /**
   * Test prepare named.
   *
   * @dataProvider sampleDataProvider
   */
  public function testPrepareNamed($sample_route, $sample_other_route, $sample_named_route) {
    $array_of_routes = [
      $sample_route,
      $sample_other_route,
      $sample_named_route,
    ];

    $route_name = $sample_named_route->getName();

    // Create our collection.
    $routes = new RouteCollection($array_of_routes);

    $original_keys = $routes->keys();

    // Prepare the named routes.
    $routes->prepareNamed();

    $this->assertNotSame($original_keys, $routes->keys());
    $this->assertSame(count($original_keys), count($routes->keys()));
    $this->assertSame($sample_named_route, $routes->get($route_name));
  }

  /**
   * Test route order doesn't change after preparing.
   *
   * @dataProvider sampleDataProvider
   */
  public function testRouteOrderDoesntChangeAfterPreparing() {
    // Get the provided data dynamically.
    $array_of_routes = func_get_args();

    // Set the number of times we should loop.
    $loop_num = 10;

    // Loop a set number of times to check different permutations.
    for ($i = 0; $i < $loop_num; $i++) {
      // Shuffle the sample routes array.
      shuffle($array_of_routes);

      // Create our collection and prepare the routes.
      $routes = new RouteCollection($array_of_routes);
      $routes->prepareNamed();

      $this->assertSame(
        array_values($routes->all()),
        array_values($array_of_routes)
      );
    }
  }

}
