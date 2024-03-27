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

use Klein\Request;
use Klein\Tests\Mocks\MockRequestFactory;

/**
 * Request Test.
 */
class RequestTest extends AbstractKleinTest {

  /**
   * Test constructor and getters.
   */
  public function testConstructorAndGetters() {
    // Test data.
    $params_get  = ['get'];
    $params_post = ['post'];
    $cookies     = ['cookies'];
    $server      = ['server'];
    $files       = ['files'];
    $body        = 'body';

    // Create the request.
    $request = new Request(
      $params_get,
      $params_post,
      $cookies,
      $server,
      $files,
      $body
    );

    // Make sure our data's the same.
    $this->assertSame($params_get, $request->paramsGet()->all());
    $this->assertSame($params_post, $request->paramsPost()->all());
    $this->assertSame($cookies, $request->cookies()->all());
    $this->assertSame($server, $request->server()->all());
    $this->assertSame($files, $request->files()->all());
    $this->assertSame($body, $request->body());
  }

  /**
   * Test globals creation.
   */
  public function testGlobalsCreation() {
    // Create a unique key.
    $key = uniqid();

    // Test data.
    // phpcs:disable
    $_GET    = array_merge($_GET, [$key => 'get']);
    $_POST   = array_merge($_POST, [$key => 'post']);
    $_COOKIE = array_merge($_COOKIE, [$key => 'cookies']);
    $_SERVER = array_merge($_SERVER, [$key => 'server']);
    $_FILES  = array_merge($_FILES, [$key => 'files']);
    // phpcs:enable

    // Create the request.
    $request = Request::createFromGlobals();

    // Make sure our data's the same.
    // phpcs:disable
    $this->assertSame($_GET[$key], $request->paramsGet()->get($key));
    $this->assertSame($_POST[$key], $request->paramsPost()->get($key));
    $this->assertSame($_COOKIE[$key], $request->cookies()->get($key));
    $this->assertSame($_SERVER[$key], $request->server()->get($key));
    $this->assertSame($_FILES[$key], $request->files()->get($key));
    // phpcs:enable
  }

  /**
   * Test universal params.
   */
  public function testUniversalParams() {
    // Test data.
    $params_get  = ['page' => 2, 'per_page' => 10, 'num' => 1, 5 => 'ok', 'empty' => NULL, 'blank' => ''];
    $params_post = ['first_name' => 'Trevor', 'last_name' => 'Suarez', 'num' => 2, 3 => 'hmm', 4 => 'thing'];
    $cookies     = ['user' => 'Rican7', 'PHPSESSID' => 'randomstring', 'num' => 3, 4 => 'dog'];
    $named       = ['id' => '1f8ae', 'num' => 4];

    // Create the request.
    $request = new Request(
      $params_get,
      $params_post,
      $cookies
    );

    // Set our named params.
    $request->paramsNamed()->replace($named);

    // Merge our params for our expected results.
    $params = array_merge($params_get, $params_post, $cookies, $named);

    $this->assertSame($params, $request->params());
    $this->assertSame($params['num'], $request->param('num'));
    $this->assertSame(NULL, $request->param('thisdoesntexist'));
  }

  /**
   * Test universal params with filter.
   */
  public function testUniversalParamsWithFilter() {
    // Test data.
    $params_get  = ['page' => 2, 'per_page' => 10, 'num' => 1, 5 => 'ok', 'empty' => NULL, 'blank' => ''];
    $params_post = ['first_name' => 'Trevor', 'last_name' => 'Suarez', 'num' => 2, 3 => 'hmm', 4 => 'thing'];
    $cookies     = ['user' => 'Rican7', 'PHPSESSID' => 'randomstring', 'num' => 3, 4 => 'dog'];

    // Create our filter and expected results.
    $filter   = ['page', 'user', 'num', 'this-key-never-showed-up-anywhere'];
    $expected = ['page' => 2, 'user' => 'Rican7', 'num' => 3, 'this-key-never-showed-up-anywhere' => NULL];

    // Create the request.
    $request = new Request(
      $params_get,
      $params_post,
      $cookies
    );

    $this->assertSame($expected, $request->params($filter));
  }

  /**
   * Test magic.
   */
  public function testMagic() {
    // Test data.
    $params = ['page' => 2, 'per_page' => 10, 'num' => 1];

    // Create the request.
    $request = new Request($params);

    // Test Exists.
    $this->assertTrue(isset($request->per_page));

    // Test Getter.
    $this->assertSame($params['per_page'], $request->per_page);

    // Test Setter.
    $this->assertSame($request->test = '#yup', $request->param('test'));

    // Test Unsetter.
    unset($request->test);
    $this->assertNull($request->param('test'));
  }

  /**
   * Test secure.
   */
  public function testSecure() {
    $request = new Request();
    $request->server()->set('HTTPS', TRUE);

    $this->assertTrue($request->isSecure());
  }

  /**
   * Test IP.
   */
  public function testIp() {
    // Test data.
    $ip = '127.0.0.1';

    $request = new Request();
    $request->server()->set('REMOTE_ADDR', $ip);

    $this->assertSame($ip, $request->ip());
  }

  /**
   * Test user agent.
   */
  public function testUserAgent() {
    // Test data.
    $user_agent = 'phpunittt';

    $request = new Request();
    $request->headers()->set('USER_AGENT', $user_agent);

    $this->assertSame($user_agent, $request->userAgent());
  }

  /**
   * Test URI.
   */
  public function testUri() {
    // Test data.
    $uri = 'localhostofthingsandstuff';
    $query = '?q=search';

    $request = new Request();
    $request->server()->set('REQUEST_URI', $uri . $query);

    $this->assertSame($uri . $query, $request->uri());
  }

  /**
   * Test path.
   */
  public function testPathname() {
    // Test data.
    $uri = 'localhostofthingsandstuff';
    $query = '?q=search';

    $request = new Request();
    $request->server()->set('REQUEST_URI', $uri . $query);

    $this->assertSame($uri, $request->pathname());
  }

  /**
   * Test body.
   */
  public function testBody() {
    // Test data.
    $body = '_why is an interesting guy<br> - Trevor';

    // Blank constructor.
    $request = new Request();

    $this->assertEmpty($request->body());

    // In constructor.
    $request = new Request([], [], [], [], [], $body);

    $this->assertSame($body, $request->body());
  }

  /**
   * Test method.
   */
  public function testMethod() {
    // Test data.
    $method = 'PATCH';

    $request = new Request();
    $request->server()->set('REQUEST_METHOD', $method);

    $this->assertSame($method, $request->method());
    $this->assertTrue($request->method($method));
    $this->assertTrue($request->method(strtolower($method)));
  }

  /**
   * Test method override.
   */
  public function testMethodOverride() {
    // Test data.
    $method                = 'POST';
    $override_method       = 'TRACE';
    $weird_override_method = 'DELETE';

    $request = new Request();
    $request->server()->set('REQUEST_METHOD', $method);
    $request->server()->set('X_HTTP_METHOD_OVERRIDE', $override_method);

    $this->assertSame($override_method, $request->method());
    $this->assertTrue($request->method($override_method));
    $this->assertTrue($request->method(strtolower($override_method)));

    $request->server()->remove('X_HTTP_METHOD_OVERRIDE');
    $request->paramsPost()->set('_method', $weird_override_method);

    $this->assertSame($weird_override_method, $request->method());
    $this->assertTrue($request->method($weird_override_method));
    $this->assertTrue($request->method(strtolower($weird_override_method)));
  }

  /**
   * Test query modify.
   */
  public function testQueryModify() {
    $test_uri = '/test?query';
    $query_string = 'search=string&page=2&per_page=3';
    $test_one = '';
    $test_two = '';
    $test_three = '';

    $request = new Request();
    $request->server()->set('REQUEST_URI', $test_uri);
    $request->server()->set('QUERY_STRING', $query_string);

    $this->kleinApp->respond(
      function ($request, $response, $service) use (&$test_one, &$test_two, &$test_three) {
        // Add a new var.
        $test_one = $request->query('test', 'dog');

        // Modify a current var.
        $test_two = $request->query('page', 7);

        // Modify a current var.
        $test_three = $request->query(['per_page' => 10]);
      }
    );

    $this->kleinApp->dispatch($request);

    $expected_uri = parse_url($this->kleinApp->request()->uri(), PHP_URL_PATH);

    $this->assertSame(
      $expected_uri . '?' . $query_string . '&test=dog',
      $test_one
    );

    $this->assertSame(
      $expected_uri . '?' . str_replace('page=2', 'page=7', $query_string),
      $test_two
    );

    $this->assertSame(
      $expected_uri . '?' . str_replace('per_page=3', 'per_page=10', $query_string),
      $test_three
    );
  }

  /**
   * Test ID.
   */
  public function testId() {
    // Create two requests.
    $request_one = new Request();
    $request_two = new Request();

    // Make sure the ID's aren't null.
    $this->assertNotNull($request_one->id());
    $this->assertNotNull($request_two->id());

    // Make sure that multiple calls yield the same result.
    $this->assertSame($request_one->id(), $request_one->id());
    $this->assertSame($request_one->id(), $request_one->id());
    $this->assertSame($request_two->id(), $request_two->id());
    $this->assertSame($request_two->id(), $request_two->id());

    // Make sure the ID's are unique to each request.
    $this->assertNotSame($request_one->id(), $request_two->id());
  }

  /**
   * Test mock factory.
   */
  public function testMockFactory() {
    // Test data.
    $uri     = '/test/uri';
    $method  = 'OPTIONS';
    $params  = ['get'];
    $cookies = ['cookies'];
    $server  = ['server'];
    $files   = ['files'];
    $body    = 'body';

    // Create the request.
    $request = MockRequestFactory::create(
      $uri,
      $method,
      $params,
      $cookies,
      $server,
      $files,
      $body
    );

    // Make sure our data's the same.
    $this->assertSame($uri, $request->uri());
    $this->assertSame($method, $request->method());
    $this->assertSame($params, $request->paramsGet()->all());

    $this->assertSame([], $request->paramsPost()->all());
    $this->assertSame([], $request->paramsNamed()->all());
    $this->assertSame($cookies, $request->cookies()->all());
    $this->assertContains($cookies[0], $request->params());
    $this->assertContains($server[0], $request->server()->all());
    $this->assertSame($files, $request->files()->all());
    $this->assertSame($body, $request->body());
  }

}
