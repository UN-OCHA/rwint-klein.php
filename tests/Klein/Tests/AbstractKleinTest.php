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

use Klein\App;
use Klein\Klein;
use PHPUnit\Framework\TestCase;

/**
 * Abstract Klein Test.
 *
 * Base test class for PHP Unit testing.
 */
abstract class AbstractKleinTest extends TestCase {

  /**
   * The automatically created test Klein instance.
   *
   * For easy testing and less boilerplate.
   *
   * @var \Klein\Klein
   */
  protected $kleinApp;

  /**
   * Store the error reporting code.
   *
   * @var int
   */
  protected $oldErrorReportingLevel;

  /**
   * Setup our test.
   *
   * It runs before each test.
   */
  protected function setUp(): void {
    $app = new class() extends App {

      /**
       * Dummy variable used by the tests.
       *
       * @var string
       *
       * @see \Klein\Tests\RoutingTest
       */
      public string $state;

    };

    // Create a new klein app, since we need one pretty much everywhere.
    $this->kleinApp = new Klein(app: $app);

    // Store the reporting level.
    $this->oldErrorReportingLevel = error_reporting();

    // Initialize the global session object.
    $_SESSION = [];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up.
    $_SESSION = [];
    @session_destroy();

    // Restore the reporting level.
    error_reporting($this->oldErrorReportingLevel);
  }

  /**
   * Dispatch and return output.
   *
   * Quick method for dispatching and returning our output from our shared Klein
   * instance.
   *
   * This is mostly useful, since the tests would otherwise have to make a bunch
   * of calls concerning the argument order and constants. DRY, bitch. ;)
   *
   * @param \Klein\Request $request
   *   Custom Klein "Request" object.
   * @param \Klein\Response $response
   *   Custom Klein "Response" object.
   *
   * @return mixed
   *   The output of the dispatch call.
   */
  protected function dispatchAndReturnOutput($request = NULL, $response = NULL) {
    return $this->kleinApp->dispatch(
      $request,
      $response,
      FALSE,
      Klein::DISPATCH_CAPTURE_AND_RETURN
    );
  }

  /**
   * Assert output same.
   *
   * Runs a callable and asserts that the output from the executed callable
   * matches the passed in expected output.
   *
   * @param mixed $expected
   *   The expected output.
   * @param callable $callback
   *   The callable function.
   * @param string $message
   *   (optional) A message to display if the assertion fails.
   */
  protected function assertOutputSame($expected, $callback, $message = '') {
    // Start our output buffer so we can capture our output.
    ob_start();

    call_user_func($callback);

    // Grab our output from our buffer.
    $out = ob_get_contents();

    // Clean our buffer and destroy it, so its like no output ever happened. ;)
    ob_end_clean();

    // Use PHPUnit's built in assertion.
    $this->assertSame($expected, $out, $message);
  }

  /**
   * Loads externally defined routes under the filename's namespace.
   *
   * @param \Klein\Klein $app_context
   *   The application context to attach the routes to.
   *
   * @return array
   *   Route namespaces.
   */
  protected function loadExternalRoutes(Klein $app_context = NULL) {
    // Did we not pass an instance?
    if (is_null($app_context)) {
      $app_context = $this->kleinApp ?: new Klein();
    }

    $route_directory = __DIR__ . '/routes/';
    $route_files = scandir($route_directory);
    $route_namespaces = [];

    foreach ($route_files as $file) {
      if (is_file($route_directory . $file)) {
        $route_namespace = '/' . basename($file, '.php');
        $route_namespaces[] = $route_namespace;

        $app_context->with($route_namespace, $route_directory . $file);
      }
    }

    return $route_namespaces;
  }

}
