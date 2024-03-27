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

namespace Klein\Tests\Mocks;

use Klein\Request;

/**
 * Mock Request Factory.
 *
 * Allow for the simple creation of mock requests
 * (great for testing... ;))
 */
class MockRequestFactory {

  /**
   * Create a new mock request.
   *
   * @param string $uri
   *   URI.
   * @param string $req_method
   *   Request method.
   * @param array $parameters
   *   Parameters.
   * @param array $cookies
   *   Cookies.
   * @param array $server
   *   Server data.
   * @param array $files
   *   Files.
   * @param string $body
   *   Body.
   */
  public static function create(
    $uri = '/',
    $req_method = 'GET',
    $parameters = [],
    $cookies = [],
    $server = [],
    $files = [],
    $body = NULL
  ) {
    // Create a new Request object.
    $request = new Request(
      [],
      [],
      $cookies,
      $server,
      $files,
      $body
    );

    // Reformat.
    $req_method = strtoupper(trim($req_method));

    // Set its URI and Method.
    $request->server()->set('REQUEST_URI', $uri);
    $request->server()->set('REQUEST_METHOD', $req_method);

    // Set our parameters.
    switch ($req_method) {
      case 'POST':
      case 'PUT':
      case 'PATCH':
      case 'DELETE':
        $request->paramsPost()->replace($parameters);
        break;

      default:
        $request->paramsGet()->replace($parameters);
        break;
    }

    return $request;
  }

}
