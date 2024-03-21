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

use Klein\DataCollection\DataCollection;
use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ServerDataCollection;

/**
 * Request.
 *
 * Base request class.
 */
class Request {

  /**
   * Class properties.
   */

  /**
   * Unique identifier for the request.
   *
   * @var string
   */
  protected string $id;

  /**
   * GET (query) parameters.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $paramsGet;

  /**
   * POST parameters.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $paramsPost;

  /**
   * Named parameters.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $paramsNamed;

  /**
   * Client cookie data.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $cookies;

  /**
   * Server created attributes.
   *
   * @var \Klein\DataCollection\ServerDataCollection
   */
  protected ServerDataCollection $server;

  /**
   * HTTP request headers.
   *
   * @var \Klein\DataCollection\HeaderDataCollection
   */
  protected HeaderDataCollection $headers;

  /**
   * Uploaded temporary files.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $files;

  /**
   * The request body.
   *
   * @var ?string
   */
  protected ?string $body;

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * Create a new Request object and define all of its request data.
   *
   * @param array<string, mixed> $params_get
   *   GET parameters.
   * @param array<string, mixed> $params_post
   *   POST parameters.
   * @param array<string, mixed> $cookies
   *   Cookies.
   * @param array<string, mixed> $server
   *   Server data.
   * @param array<string, mixed> $files
   *   File data.
   * @param ?string $body
   *   Body.
   */
  final public function __construct(
    array $params_get = [],
    array $params_post = [],
    array $cookies = [],
    array $server = [],
    array $files = [],
    ?string $body = NULL
  ) {
    // Assignments.
    $this->paramsGet  = new DataCollection($params_get);
    $this->paramsPost = new DataCollection($params_post);
    $this->cookies    = new DataCollection($cookies);
    $this->server     = new ServerDataCollection($server);
    $this->headers    = new HeaderDataCollection($this->server->getHeaders());
    $this->files      = new DataCollection($files);
    $this->body       = isset($body) ? (string) $body : NULL;

    // Non-injected assignments.
    $this->paramsNamed = new DataCollection();
  }

  /**
   * Create a new request object using the built-in "superglobals".
   *
   * @return \Klein\Request
   *   New request.
   *
   * @link http://php.net/manual/en/language.variables.superglobals.php
   */
  public static function createFromGlobals(): Request {
    // Create and return a new instance of this.
    return new static(
      // phpcs:disable
      $_GET,
      $_POST,
      $_COOKIE,
      $_SERVER,
      $_FILES,
      // phpcs:enable
      // Let our content getter take care of the "body".
      NULL
    );
  }

  /**
   * Gets a unique ID for the request.
   *
   * Generates one on the first call.
   *
   * @param bool $hash
   *   Whether or not to hash the ID on creation.
   *
   * @return string
   *   Unique ID.
   */
  public function id(bool $hash = TRUE): string {
    if (!isset($this->id)) {
      $this->id = uniqid();

      if ($hash) {
        $this->id = sha1($this->id);
      }
    }

    return $this->id;
  }

  /**
   * Returns the GET parameters collection.
   *
   * @return \Klein\DataCollection\DataCollection
   *   GET parameters.
   */
  public function paramsGet(): DataCollection {
    return $this->paramsGet;
  }

  /**
   * Returns the POST parameters collection.
   *
   * @return \Klein\DataCollection\DataCollection
   *   POST parameters.
   */
  public function paramsPost(): DataCollection {
    return $this->paramsPost;
  }

  /**
   * Returns the named parameters collection.
   *
   * @return \Klein\DataCollection\DataCollection
   *   Named parameters.
   */
  public function paramsNamed(): DataCollection {
    return $this->paramsNamed;
  }

  /**
   * Returns the cookies collection.
   *
   * @return \Klein\DataCollection\DataCollection
   *   Cookies.
   */
  public function cookies(): DataCollection {
    return $this->cookies;
  }

  /**
   * Returns the server collection.
   *
   * @return \Klein\DataCollection\ServerDataCollection
   *   Server data.
   */
  public function server(): ServerDataCollection {
    return $this->server;
  }

  /**
   * Returns the headers collection.
   *
   * @return \Klein\DataCollection\HeaderDataCollection
   *   Headers.
   */
  public function headers(): HeaderDataCollection {
    return $this->headers;
  }

  /**
   * Returns the files collection.
   *
   * @return \Klein\DataCollection\DataCollection
   *   File data.
   */
  public function files(): DataCollection {
    return $this->files;
  }

  /**
   * Gets the request body.
   *
   * @return ?string
   *   Body.
   */
  public function body(): ?string {
    // Only get it once.
    if (!isset($this->body)) {
      $this->body = @file_get_contents('php://input') ?: NULL;
    }

    return $this->body;
  }

  /**
   * Returns all parameters (GET, POST, named, and cookies) that match the mask.
   *
   * Takes an optional mask param that contains the names of any params
   * you'd like this method to exclude in the returned array.
   *
   * @param ?array<string|int> $mask
   *   The parameter mask array.
   * @param bool $fill_with_nulls
   *   Whether or not to fill the returned array with null values to match the
   *   given mask.
   *
   * @return mixed[]
   *   Parameters.
   *
   * @see \Klein\DataCollection\DataCollection::all()
   */
  public function params(?array $mask = NULL, bool $fill_with_nulls = TRUE): array {
    /*
     * Make sure that each key in the mask has at least a
     * null value, since the user will expect the key to exist
     */
    if (NULL !== $mask && $fill_with_nulls) {
      $attributes = array_fill_keys($mask, NULL);
    }
    else {
      $attributes = [];
    }

    // Merge our params in the get, post, cookies, named order.
    return array_merge(
      $attributes,
      $this->paramsGet->all($mask, FALSE),
      $this->paramsPost->all($mask, FALSE),
      $this->cookies->all($mask, FALSE),
      // Add our named params last.
      $this->paramsNamed->all($mask, FALSE)
    );
  }

  /**
   * Return a request parameter, or $default if it doesn't exist.
   *
   * @param string $key
   *   The name of the parameter to return.
   * @param mixed $default
   *   The default value of the parameter if it contains no value.
   *
   * @return mixed
   *   Parameter value.
   */
  public function param(string $key, mixed $default = NULL): mixed {
    // Get all of our request params.
    $params = $this->params();

    return $params[$key] ?? $default;
  }

  /**
   * Magic "__isset" method.
   *
   * Allows the ability to arbitrarily check the existence of a parameter
   * from this instance while treating it as an instance property.
   *
   * @param string $param
   *   The name of the parameter.
   *
   * @return bool
   *   TRUE if the parameter exists.
   */
  public function __isset(string $param): bool {
    // Get all of our request params.
    $params = $this->params();

    return isset($params[$param]);
  }

  /**
   * Magic "__get" method.
   *
   * Allows the ability to arbitrarily request a parameter from this instance
   * while treating it as an instance property.
   *
   * @param string $param
   *   The name of the parameter.
   *
   * @return mixed
   *   Parameter value.
   */
  public function __get(string $param): mixed {
    return $this->param($param);
  }

  /**
   * Magic "__set" method.
   *
   * Allows the ability to arbitrarily set a parameter from this instance
   * while treating it as an instance property.
   *
   * NOTE: This currently sets the "named" parameters, since that's the
   * one collection that we have the most sane control over.
   *
   * @param string $param
   *   The name of the parameter.
   * @param mixed $value
   *   The value of the parameter.
   */
  public function __set(string $param, mixed $value): void {
    $this->paramsNamed->set($param, $value);
  }

  /**
   * Magic "__unset" method.
   *
   * Allows the ability to arbitrarily remove a parameter from this instance
   * while treating it as an instance property.
   *
   * @param string $param
   *   The name of the parameter.
   */
  public function __unset(string $param): void {
    $this->paramsNamed->remove($param);
  }

  /**
   * Is the request secure?
   *
   * @return bool
   *   TRUE if secure.
   */
  public function isSecure(): bool {
    return ($this->server->get('HTTPS', FALSE) == TRUE);
  }

  /**
   * Gets the request IP address.
   *
   * @return string
   *   IP address.
   */
  public function ip(): string {
    $ip = $this->server->get('REMOTE_ADDR', '');
    return is_string($ip) ? $ip : '';
  }

  /**
   * Gets the request user agent.
   *
   * @return string
   *   User agent.
   */
  public function userAgent(): string {
    $user_agent = $this->headers->get('USER_AGENT', '');
    return is_string($user_agent) ? $user_agent : '';
  }

  /**
   * Gets the request URI.
   *
   * @return string
   *   Request URI.
   */
  public function uri(): string {
    $uri = $this->server->get('REQUEST_URI', '');
    return is_string($uri) ? $uri : '';
  }

  /**
   * Get the request's pathname.
   *
   * @return string
   *   Path.
   */
  public function pathname(): string {
    $uri = $this->uri();

    // Strip the query string from the URI.
    $uri = strstr($uri, '?', TRUE) ?: $uri;

    return $uri;
  }

  /**
   * Gets the request method, or checks it against $is.
   *
   * <code>
   * // POST request example
   * $request->method() // returns 'POST'
   * $request->method('post') // returns TRUE
   * $request->method('get') // returns FALSE
   * </code>
   *
   * @param ?string $is
   *   The method to check the current request method against.
   * @param bool $allow_override
   *   Whether or not to allow HTTP method overriding via header or params.
   *
   * @return string|bool
   *   The method name or if $is is set, TRUE if the method equals $is,
   *   FALSE otherwise.
   */
  public function method(?string $is = NULL, bool $allow_override = TRUE): string|bool {
    $method = $this->server->get('REQUEST_METHOD', 'GET');
    $method = is_string($method) ? $method : 'GET';

    // Override.
    if ($allow_override && $method === 'POST') {
      // For legacy servers, override the HTTP method with the
      // X-HTTP-Method-Override header or _method parameter.
      if ($this->server->exists('X_HTTP_METHOD_OVERRIDE')) {
        $method_override = $this->server->get('X_HTTP_METHOD_OVERRIDE', $method);
        $method = is_string($method_override) ? $method_override : $method;
      }
      else {
        $param_method = $this->param('_method', $method);
        $method = is_string($param_method) ? $param_method : $method;
      }

      $method = strtoupper($method);
    }

    // We're doing a check.
    if (NULL !== $is) {
      return strcasecmp($method, $is) === 0;
    }

    return $method;
  }

  /**
   * Adds to or modifies the current query string.
   *
   * @param string|array<string, mixed>|null $key
   *   The name of the query param or an array in the form key => value.
   * @param mixed $value
   *   The value of the query param.
   *
   * @return string
   *   Query string.
   */
  public function query(string|array|null $key = NULL, mixed $value = NULL): string {
    $query = [];

    $query_string = $this->server()->get('QUERY_STRING', '');
    $query_string = is_string($query_string) ? $query_string : '';

    parse_str(
      $query_string,
      $query
    );

    if (!is_null($key)) {
      if (is_array($key)) {
        $query = array_merge($query, $key);
      }
      else {
        $query[$key] = $value;
      }
    }

    $request_uri = $this->uri();

    if (strpos($request_uri, '?') !== FALSE) {
      $request_uri = strstr($request_uri, '?', TRUE);
    }

    return $request_uri . (!empty($query) ? '?' . http_build_query($query) : NULL);
  }

}
