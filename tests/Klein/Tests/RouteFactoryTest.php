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

namespace Klein\Tests;

use Klein\Route;
use Klein\RouteFactory;

/**
 * Route Factory Test.
 */
class RouteFactoryTest extends AbstractKleinTest {

  /**
   * Constants.
   */

  public const TEST_CALLBACK_MESSAGE = 'yay';

  /**
   * Helpers.
   */

  /**
   * Get test callable.
   */
  protected function getTestCallable($message = self::TEST_CALLBACK_MESSAGE) {
    return function () use ($message) {
      return $message;
    };
  }

  /**
   * Tests.
   */

  /**
   * Test build basic.
   */
  public function testBuildBasic(
    $test_namespace = NULL,
    $test_path = NULL,
    $test_paths_match = TRUE,
    $should_match = TRUE,
  ) {
    // Test data.
    $test_path = is_string($test_path) ? $test_path : '/test';
    $test_callable = $this->getTestCallable();

    $factory = new RouteFactory($test_namespace ?? '');

    $route = $factory->build(
      $test_callable,
      $test_path
    );

    $this->assertTrue($route instanceof Route);
    $this->assertNull($route->getMethod());
    $this->assertNull($route->getName());
    $this->assertSame($test_callable(), $route());

    $this->assertSame($should_match, $route->getCountMatch());

    if ($test_paths_match) {
      $this->assertSame($test_path, $route->getPath());
    }
  }

  /**
   * Test build with namespaced path.
   */
  public function testBuildWithNamespacedPath() {
    // Test data.
    $test_namespace = '/users';
    $test_path = '/test';

    $this->testBuildBasic($test_namespace, $test_path, FALSE);
  }

  /**
   * Test build with namespaced catch all path.
   */
  public function testBuildWithNamespacedCatchAllPath() {
    // Test data.
    $test_namespace = '/users';
    $test_path = '*';

    $this->testBuildBasic($test_namespace, $test_path, FALSE, FALSE);
  }

  /**
   * Test build with namespace null path.
   */
  public function testBuildWithNamespacedNullPath() {
    // Test data.
    $test_namespace = '/users';

    $this->testBuildBasic($test_namespace, NULL, FALSE);
  }

  /**
   * Test build with namespaced empty path.
   */
  public function testBuildWithNamespacedEmptyPath() {
    // Test data.
    $test_namespace = '/users';
    $test_path = '';

    $this->testBuildBasic($test_namespace, $test_path, FALSE, TRUE);
  }

  /**
   * Test build with custom regex path.
   */
  public function testBuildWithCustomRegexPath() {
    // Test data.
    $test_path = '@/test';

    $this->testBuildBasic(NULL, $test_path);
  }

  /**
   * Test build with custom regex namespaced path.
   */
  public function testBuildWithCustomRegexNamespacedPath() {
    // Test data.
    $test_namespace = '/users';
    $test_path = '@/test';

    $this->testBuildBasic($test_namespace, $test_path, FALSE);
  }

  /**
   * Test build with custom negated regex path.
   */
  public function testBuildWithCustomNegatedRegexPath() {
    // Test data.
    $test_path = '!@/test';

    $this->testBuildBasic(NULL, $test_path, FALSE);
  }

  /**
   * Test build with custom negate anchored regex path.
   */
  public function testBuildWithCustomNegatedAnchoredRegexPath() {
    // Test data.
    $test_path = '!@^/test';

    $this->testBuildBasic(NULL, $test_path, FALSE);
  }

}
