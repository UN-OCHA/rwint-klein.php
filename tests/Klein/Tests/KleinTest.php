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
use Klein\DataCollection\RouteCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\HttpExceptionInterface;
use Klein\Exceptions\UnhandledException;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use Klein\Route;
use Klein\ServiceProvider;

/**
 * Klein Test.
 */
class KleinTest extends AbstractKleinTest {

  /**
   * Constants.
   */

  const TEST_CALLBACK_MESSAGE = 'yay';

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
   * Test constructor.
   */
  public function testConstructor() {
    $klein = new Klein();

    $this->assertNotNull($klein);
    $this->assertTrue($klein instanceof Klein);
  }

  /**
   * Test service.
   */
  public function testService() {
    $service = $this->kleinApp->service();

    $this->assertNotNull($service);
    $this->assertTrue($service instanceof ServiceProvider);
  }

  /**
   * Test app.
   */
  public function testApp() {
    $app = $this->kleinApp->app();

    $this->assertNotNull($app);
    $this->assertTrue($app instanceof App);
  }

  /**
   * Test routes.
   */
  public function testRoutes() {
    $routes = $this->kleinApp->routes();

    $this->assertNotNull($routes);
    $this->assertTrue($routes instanceof RouteCollection);
  }

  /**
   * Test request.
   */
  public function testRequest() {
    $this->kleinApp->dispatch();

    $request = $this->kleinApp->request();

    $this->assertNotNull($request);
    $this->assertTrue($request instanceof Request);
  }

  /**
   * Test response.
   */
  public function testResponse() {
    $this->kleinApp->dispatch();

    $response = $this->kleinApp->response();

    $this->assertNotNull($response);
    $this->assertTrue($response instanceof Response);
  }

  /**
   * Test respond.
   */
  public function testRespond() {
    $route = $this->kleinApp->respond($this->getTestCallable());

    $object_id = spl_object_hash($route);

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertTrue($this->kleinApp->routes()->exists($object_id));
    $this->assertSame($route, $this->kleinApp->routes()->get($object_id));
  }

  /**
   * Test with.
   */
  public function testWith() {
    // Test data.
    $test_namespace = '/test/namespace';
    $passed_context = NULL;

    $this->kleinApp->with(
      $test_namespace,
      function ($context) use (&$passed_context) {
        $passed_context = $context;
      }
    );

    $this->assertTrue($passed_context instanceof Klein);
  }

  /**
   * Test with string callable.
   */
  public function testWithStringCallable() {
    // Test data.
    $test_namespace = '/test/namespace';

    $this->kleinApp->with(
      $test_namespace,
      'test_num_args_wrapper'
    );

    $this->expectOutputString('1');
  }

  /**
   * Test with using file include.
   *
   * Weird PHPUnit bug is causing scope errors for the
   * isolated process tests, unless I run this also in an
   * isolated process.
   *
   * @runInSeparateProcess */
  public function testWithUsingFileInclude() {
    // Test data.
    $test_namespace = '/test/namespace';
    $test_routes_include = __DIR__ . '/routes/random.php';

    // Test file include.
    $this->assertEmpty($this->kleinApp->routes()->all());
    $this->kleinApp->with($test_namespace, $test_routes_include);

    $this->assertNotEmpty($this->kleinApp->routes()->all());

    $all_routes = array_values($this->kleinApp->routes()->all());
    $test_route = $all_routes[0];

    $this->assertTrue($test_route instanceof Route);
    $this->assertSame($test_namespace . '/?', $test_route->getPath());
  }

  /**
   * Test dispatch.
   */
  public function testDispatch() {
    $request = new Request();
    $response = new Response();

    $this->kleinApp->dispatch($request, $response);

    $this->assertSame($request, $this->kleinApp->request());
    $this->assertSame($response, $this->kleinApp->response());
  }

  /**
   * Test get path for.
   */
  public function testGetPathFor() {
    // Test data.
    $test_path = '/test';
    $test_name = 'Test Route Thing';

    $route = new Route($this->getTestCallable());
    $route->setPath($test_path);
    $route->setName($test_name);

    $this->kleinApp->routes()->addRoute($route);

    // Make sure it fails if not prepared.
    try {
      $this->kleinApp->getPathFor($test_name);
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof \OutOfBoundsException);
    }

    $this->kleinApp->routes()->prepareNamed();

    $returned_path = $this->kleinApp->getPathFor($test_name);

    $this->assertNotEmpty($returned_path);
    $this->assertSame($test_path, $returned_path);
  }

  /**
   * Test on error with string callables.
   */
  public function testOnErrorWithStringCallables() {
    $this->kleinApp->onError('test_num_args_wrapper');

    $this->kleinApp->respond(
      function ($request, $response, $service) {
        throw new \Exception('testing');
      }
    );

    $this->assertSame(
      '4',
      $this->dispatchAndReturnOutput()
    );
  }

  /**
   * Tst on error with bad callables.
   */
  public function testOnErrorWithBadCallables() {
    $this->kleinApp->onError('this_function_doesnt_exist');

    $this->kleinApp->respond(
      function ($request, $response, $service) {
        throw new \Exception('testing');
      }
    );

    $this->assertEmpty($this->kleinApp->service()->flashes());

    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput()
    );

    $this->assertNotEmpty($this->kleinApp->service()->flashes());

    // Clean up.
    session_destroy();
  }

  /**
   * Test on http error.
   */
  public function testOnHttpError() {
    // Create expected arguments.
    $num_of_args = 0;
    $expected_arguments = [
      'code'      => NULL,
      'klein'      => NULL,
      'matched'     => NULL,
      'methods_matched' => NULL,
      'exception'    => NULL,
    ];

    $this->kleinApp->onHttpError(
      function ($code, $klein, $matched, $methods_matched, $exception) use (&$num_of_args, &$expected_arguments) {
        // Keep track of our arguments.
        $num_of_args = func_num_args();
        $expected_arguments['code'] = $code;
        $expected_arguments['klein'] = $klein;
        $expected_arguments['matched'] = $matched;
        $expected_arguments['methods_matched'] = $methods_matched;
        $expected_arguments['exception'] = $exception;

        $klein->response()->body($code . ' error');
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->assertSame(
      '404 error',
      $this->kleinApp->response()->body()
    );

    $this->assertSame(count($expected_arguments), $num_of_args);

    $this->assertTrue(is_int($expected_arguments['code']));
    $this->assertTrue($expected_arguments['klein'] instanceof Klein);
    $this->assertTrue($expected_arguments['matched'] instanceof RouteCollection);
    $this->assertTrue(is_array($expected_arguments['methods_matched']));
    $this->assertTrue($expected_arguments['exception'] instanceof HttpExceptionInterface);

    $this->assertSame($expected_arguments['klein'], $this->kleinApp);
  }

  /**
   * Test on http error with string callables.
   */
  public function testOnHttpErrorWithStringCallables() {
    $this->kleinApp->onHttpError('test_num_args_wrapper');

    $this->assertSame(
      '5',
      $this->dispatchAndReturnOutput()
    );
  }

  /**
   * Test on http error with bad callables.
   */
  public function testOnHttpErrorWithBadCallables() {
    $this->kleinApp->onError('this_function_doesnt_exist');

    $this->assertSame(
      '',
      $this->dispatchAndReturnOutput()
    );
  }

  /**
   * Test after dispatch.
   */
  public function testAfterDispatch() {
    $this->kleinApp->afterDispatch(
      function ($klein) {
        $klein->response()->body('after callbacks!');
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->assertSame(
      'after callbacks!',
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test after dispatch with multiple callbacks.
   */
  public function testAfterDispatchWithMultipleCallbacks() {
    $this->kleinApp->afterDispatch(
      function ($klein) {
        $klein->response()->body('after callbacks!');
      }
    );

    $this->kleinApp->afterDispatch(
      function ($klein) {
        $klein->response()->body('whatever');
      }
    );

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->assertSame(
      'whatever',
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test after dispatch with string callables.
   */
  public function testAfterDispatchWithStringCallables() {
    $this->kleinApp->afterDispatch('test_response_edit_wrapper');

    $this->kleinApp->dispatch(NULL, NULL, FALSE);

    $this->assertSame(
      'after callbacks!',
      $this->kleinApp->response()->body()
    );
  }

  /**
   * Test after dispatch with callable that throws exception.
   */
  public function testAfterDispatchWithCallableThatThrowsException() {
    $this->expectException(UnhandledException::class);

    $this->kleinApp->afterDispatch(
      function ($klein) {
        throw new \Exception('testing');
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame(
      500,
      $this->kleinApp->response()->code()
    );
  }

  /**
   * Test error with no callbacks.
   */
  public function testErrorsWithNoCallbacks() {
    $this->expectException(UnhandledException::class);

    $this->kleinApp->respond(
      function ($request, $response, $service) {
        throw new \Exception('testing');
      }
    );

    $this->kleinApp->dispatch();

    $this->assertSame(
      500,
      $this->kleinApp->response()->code()
    );
  }

  /**
   * Test skip this.
   */
  public function testSkipThis() {
    try {
      $this->kleinApp->skipThis();
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof DispatchHaltedException);
      $this->assertSame(DispatchHaltedException::SKIP_THIS, $e->getCode());
      $this->assertSame(1, $e->getNumberOfSkips());
    }
  }

  /**
   * Test skip next.
   */
  public function testSkipNext() {
    $number_of_skips = 3;

    try {
      $this->kleinApp->skipNext($number_of_skips);
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof DispatchHaltedException);
      $this->assertSame(DispatchHaltedException::SKIP_NEXT, $e->getCode());
      $this->assertSame($number_of_skips, $e->getNumberOfSkips());
    }
  }

  /**
   * Test skip remaining.
   */
  public function testSkipRemaining() {
    try {
      $this->kleinApp->skipRemaining();
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof DispatchHaltedException);
      $this->assertSame(DispatchHaltedException::SKIP_REMAINING, $e->getCode());
    }
  }

  /**
   * Test abort.
   */
  public function testAbort() {
    $test_code = 503;

    $this->kleinApp->respond(
      function ($a, $b, $c, $d, $kleinApp) use ($test_code) {
        $kleinApp->abort($test_code);
      }
    );

    try {
      $this->kleinApp->dispatch();
    }
    catch (\Exception $e) {
      $this->assertTrue($e instanceof DispatchHaltedException);
      $this->assertSame(DispatchHaltedException::SKIP_REMAINING, $e->getCode());
    }

    $this->assertSame($test_code, $this->kleinApp->response()->code());
    $this->assertTrue($this->kleinApp->response()->isLocked());
  }

  /**
   * Test options.
   */
  public function testOptions() {
    $route = $this->kleinApp->options($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('OPTIONS', $route->getMethod());
  }

  /**
   * Test head.
   */
  public function testHead() {
    $route = $this->kleinApp->head($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('HEAD', $route->getMethod());
  }

  /**
   * Test get.
   */
  public function testGet() {
    $route = $this->kleinApp->get($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('GET', $route->getMethod());
  }

  /**
   * Test post.
   */
  public function testPost() {
    $route = $this->kleinApp->post($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('POST', $route->getMethod());
  }

  /**
   * Test put.
   */
  public function testPut() {
    $route = $this->kleinApp->put($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('PUT', $route->getMethod());
  }

  /**
   * Test delete.
   */
  public function testDelete() {
    $route = $this->kleinApp->delete($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('DELETE', $route->getMethod());
  }

  /**
   * Test patch.
   */
  public function testPatch() {
    $route = $this->kleinApp->patch($this->getTestCallable());

    $this->assertNotNull($route);
    $this->assertTrue($route instanceof Route);
    $this->assertSame('PATCH', $route->getMethod());
  }

}
