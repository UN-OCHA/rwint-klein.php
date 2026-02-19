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

use Klein\DataCollection\RouteCollection;
use Klein\Exceptions\DispatchHaltedException;
use Klein\Exceptions\HttpException;
use Klein\Exceptions\HttpExceptionInterface;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\RegularExpressionCompilationException;
use Klein\Exceptions\RoutePathCompilationException;
use Klein\Exceptions\UnhandledException;

/**
 * Klein.
 *
 * Main Klein router class.
 */
class Klein {

  /**
   * Class constants.
   */

  /**
   * Regular expression to compile and match URL's.
   *
   * @type string
   */
  public const ROUTE_COMPILE_REGEX = '`(\\\?(?:/|\.|))(?:\[([^:\]]*)(?::([^:\]]*))?\])(\?|)`';

  /**
   * Regular expression to escape the non-named param section of a route URL.
   *
   * @type string
   */
  public const ROUTE_ESCAPE_REGEX = '`(?<=^|\])[^\]\[\?]+?(?=\[|$)`';

  /**
   * Dispatch route output handling.
   *
   * Don't capture anything. Behave as normal.
   *
   * @type int
   */
  public const DISPATCH_NO_CAPTURE = 0;

  /**
   * Dispatch route output handling.
   *
   * Capture all output and return it from dispatch.
   *
   * @type int
   */
  public const DISPATCH_CAPTURE_AND_RETURN = 1;

  /**
   * Dispatch route output handling.
   *
   * Capture all output and replace the response body with it.
   *
   * @type int
   */
  public const DISPATCH_CAPTURE_AND_REPLACE = 2;

  /**
   * Dispatch route output handling.
   *
   * Capture all output and prepend it to the response body.
   *
   * @type int
   */
  public const DISPATCH_CAPTURE_AND_PREPEND = 3;

  /**
   * Dispatch route output handling.
   *
   * Capture all output and append it to the response body.
   *
   * @type int
   */
  public const DISPATCH_CAPTURE_AND_APPEND = 4;


  /**
   * Class properties.
   */

  /**
   * The types to detect in a defined match "block".
   *
   * Examples of these blocks are as follows:
   *
   * - integer:    '[i:id]'
   * - alphanumeric: '[a:username]'
   * - hexadecimal:  '[h:color]'
   * - slug:     '[s:article]'
   *
   * @var array<string, string>
   */
  protected array $matchTypes = [
    'i' => '[0-9]++',
    'a' => '[0-9A-Za-z]++',
    'h' => '[0-9A-Fa-f]++',
    's' => '[0-9A-Za-z-_]++',
    '*' => '.+?',
    '**' => '.++',
    ''  => '[^/]+?',
  ];

  /**
   * Collection of the routes to match on dispatch.
   *
   * @var \Klein\DataCollection\RouteCollection
   */
  protected RouteCollection $routes;

  /**
   * The Route factory object responsible for creating Route instances.
   *
   * @var \Klein\AbstractRouteFactory
   */
  protected AbstractRouteFactory $routeFactory;

  /**
   * A stack of error callback callables.
   *
   * @var \SplStack<callable|string>
   */
  protected \SplStack $errorCallbacks;

  /**
   * A stack of HTTP error callback callables.
   *
   * @var \SplStack<callable|\Klein\Route>
   */
  protected \SplStack $httpErrorCallbacks;

  /**
   * Queue of after filter callbacks.
   *
   * A queue of callbacks to call after processing the dispatch loop
   * and before the response is sent.
   *
   * @var \SplQueue<callable|string>
   */
  protected \SplQueue $afterFilterCallbacks;

  /**
   * The output buffer level used by the dispatch process.
   *
   * @var int
   */
  private int $outputBufferLevel;

  /**
   * Route objects.
   */

  /**
   * The Request object passed to each matched route.
   *
   * @var \Klein\Request
   */
  protected Request $request;

  /**
   * The Response object passed to each matched route.
   *
   * @var \Klein\AbstractResponse
   */
  protected AbstractResponse $response;

  /**
   * The service provider object passed to each matched route.
   *
   * @var \Klein\ServiceProvider
   */
  protected ServiceProvider $service;

  /**
   * A generic variable passed to each matched route.
   *
   * @var mixed
   */
  protected mixed $app;

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * Create a new Klein instance with optionally injected dependencies.
   * This DI allows for easy testing, object mocking, or class extension.
   *
   * @param ServiceProvider $service
   *   Service provider object responsible for utilitarian behaviors.
   * @param mixed $app
   *   An object passed to each route callback, defaults to an App instance.
   * @param \Klein\DataCollection\RouteCollection $routes
   *   Collection object responsible for containing all route instances.
   * @param \Klein\AbstractRouteFactory $route_factory
   *   A factory class responsible for creating Route instances.
   */
  public function __construct(
    ?ServiceProvider $service = NULL,
    mixed $app = NULL,
    ?RouteCollection $routes = NULL,
    ?AbstractRouteFactory $route_factory = NULL,
  ) {
    // Instanciate and fall back to defaults.
    $this->service = $service ?: new ServiceProvider();
    $this->app = $app ?: new App();
    $this->routes = $routes ?: new RouteCollection();
    $this->routeFactory = $route_factory ?: new RouteFactory();

    $this->errorCallbacks = new \SplStack();
    $this->httpErrorCallbacks = new \SplStack();
    $this->afterFilterCallbacks = new \SplQueue();
  }

  /**
   * Returns the routes object.
   *
   * @return \Klein\DataCollection\RouteCollection
   *   The route collection.
   */
  public function routes(): RouteCollection {
    return $this->routes;
  }

  /**
   * Returns the request object.
   *
   * @return \Klein\Request
   *   The request object.
   */
  public function request(): Request {
    return $this->request;
  }

  /**
   * Returns the response object.
   *
   * @return \Klein\AbstractResponse
   *   The response object.
   */
  public function response(): AbstractResponse {
    return $this->response;
  }

  /**
   * Returns the service object.
   *
   * @return \Klein\ServiceProvider
   *   The service object.
   */
  public function service(): ServiceProvider {
    return $this->service;
  }

  /**
   * Returns the app object.
   *
   * @return mixed
   *   The app object.
   */
  public function app(): mixed {
    return $this->app;
  }

  /**
   * Parse our extremely loose argument order.
   *
   * Parse our extremely loose argument order of our "respond" method and its
   * aliases.
   *
   * This method takes its arguments in a loose format and order.
   * The method signature is simply there for documentation purposes, but allows
   * for the minimum of a callback to be passed in its current configuration.
   *
   * @param mixed[] $args
   *   An argument array. Hint: This works well when passing "func_get_args()".
   *   It should contain:
   *   - $method (array|string): HTTP Method to match.
   *   - $path (string) Route URI path to match.
   *   - $callback (callable): Callable callback method to execute on route
   *     match.
   *
   * @return mixed[]
   *   A named parameter array containing the keys: 'method', 'path', and
   *   'callback'.
   *
   * @see ::respond()
   */
  protected function parseLooseArgumentOrder(array $args): array {
    return [
      'callback' => array_pop($args),
      'path' => array_pop($args),
      'method' => array_pop($args),
    ];
  }

  /**
   * Add a new route to be matched on dispatch.
   *
   * Essentially, this method is a standard "Route" builder/factory,
   * allowing a loose argument format and a standard way of creating
   * Route instances.
   *
   * This method takes its arguments in a very loose format
   * The only "required" parameter is the callback (which is very strange
   * considering the argument definition order).
   *
   * <code>
   * $router = new Klein();
   *
   * $router->respond( function() {
   *   echo 'this works';
   * });
   * $router->respond( '/endpoint', function() {
   *   echo 'this also works';
   * });
   * $router->respond( 'POST', '/endpoint', function() {
   *   echo 'this also works!!!!';
   * });
   * </code>
   *
   * @return \Klein\Route
   *   The new route.
   *
   * @see ::doRespond()
   */
  public function respond(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());

    $callback = is_callable($args['callback']) ? $args['callback'] : NULL;
    $path = is_string($args['path']) || is_int($args['path']) ? (string) $args['path'] : RouteFactory::NULL_PATH_VALUE;
    $method = NULL;
    if (isset($args['method'])) {
      if (is_string($args['method'])) {
        $method = $args['method'];
      }
      elseif (is_array($args['method'])) {
        $method = array_filter(array_values(array_map(static function (mixed $m): string {
          return is_scalar($m) ? (string) $m : '';
        }, $args['method'])));
      }
    }

    return $this->doRespond($callback, $path, $method);
  }

  /**
   * Add a new route to be matched on dispatch.
   *
   * As opposed to ::respond(), this method expects parameters in order.
   *
   * @param ?callable $callback
   *   Callable callback method to execute on route match.
   * @param ?string $path
   *   Route URI path to match.
   * @param string|array<string>|null $method
   *   HTTP Method to match. It can be an array of method. Defaults to GET if
   *   NULL.
   *
   * @return \Klein\Route
   *   The new route.
   *
   * @throws \RuntimeException
   *   Exception if the callback is missing.
   */
  protected function doRespond(
    ?callable $callback = NULL,
    ?string $path = RouteFactory::NULL_PATH_VALUE,
    string|array|null $method = NULL,
  ): Route {
    if (is_null($callback)) {
      throw new \RuntimeException('Missing callback to respond.');
    }

    $route = $this->routeFactory->build($callback, $path, $method);

    $this->routes->add($route);

    return $route;
  }

  /**
   * Collect a set of routes under a common namespace.
   *
   * The routes may be passed in as either a callable (which holds the route
   * definitions), or as a string of a filename, of which to "include" under
   * the Klein router scope.
   *
   * <code>
   * $router = new Klein();
   *
   * $router->with('/users', function($router) {
   *   $router->respond( '/', function() {
   *     // do something interesting
   *   });
   *   $router->respond( '/[i:id]', function() {
   *     // do something different
   *   });
   * });
   *
   * $router->with('/cars', __DIR__ . '/routes/cars.php');
   * </code>
   *
   * @param string $namespace
   *   The namespace under which to collect the routes.
   * @param callable|string $routes
   *   The defined routes callable or filename to collect under the namespace.
   */
  public function with(string $namespace, callable|string $routes): void {
    $previous = $this->routeFactory->getNamespace();

    $this->routeFactory->appendNamespace($namespace);

    if (is_callable($routes)) {
      if (is_string($routes)) {
        $routes($this);
      }
      else {
        call_user_func($routes, $this);
      }
    }
    else {
      include $routes;
    }

    $this->routeFactory->setNamespace($previous);
  }

  /**
   * Dispatch the request to the appropriate route(s).
   *
   * Dispatch with optionally injected dependencies.
   * This DI allows for easy testing, object mocking, or class extension.
   *
   * @param Request $request
   *   The request object to give to each callback.
   * @param AbstractResponse $response
   *   The response object to give to each callback.
   * @param bool $send_response
   *   Whether or not to "send" the response after the last route has been
   *   matched.
   * @param int $capture
   *   Specify a DISPATCH_* constant to change the output capturing behavior.
   *
   * @return string
   *   The result to display or nothing.
   */
  public function dispatch(
    ?Request $request = NULL,
    ?AbstractResponse $response = NULL,
    bool $send_response = TRUE,
    int $capture = self::DISPATCH_NO_CAPTURE,
  ): string {
    // Set/Initialize our objects to be sent in each callback.
    $this->request = $request ?: Request::createFromGlobals();
    $this->response = $response ?: new Response();

    // Bind our objects to our service.
    $this->service->bind($this->request, $this->response);

    // Prepare any named routes.
    $this->routes->prepareNamed();

    // Grab some data from the request.
    $uri = $this->request->pathname();
    $req_method = (string) $this->request->method();

    // Set up some variables for matching.
    $skip_num = 0;
    /** @var \Klein\DataCollection\RouteCollection $matched */
    // Get a clone of the routes collection, as it may have been injected.
    $matched = $this->routes->cloneEmpty();
    $methods_matched = [];
    $params = [];
    $apc = function_exists('apc_fetch');

    // Start output buffering.
    ob_start();
    $this->outputBufferLevel = ob_get_level();

    try {
      foreach ($this->routes as $route) {
        if (!($route instanceof Route)) {
          continue;
        }
        // Are we skipping any matches?
        if ($skip_num > 0) {
          $skip_num--;
          continue;
        }

        // Grab the properties of the route handler.
        $method = $route->getMethod();
        $path = $route->getPath();
        $count_match = $route->getCountMatch();
        $match = FALSE;

        // Keep track of whether this specific request method was matched.
        $method_match = NULL;

        // Was a method specified? If so, check it against the current request
        // method.
        if (is_array($method)) {
          foreach ($method as $test) {
            if (strcasecmp($req_method, $test) === 0) {
              $method_match = TRUE;
            }
            elseif (
              strcasecmp($req_method, 'HEAD') === 0
              && (strcasecmp($test, 'HEAD') === 0 || strcasecmp($test, 'GET') === 0)
            ) {
              // Test for HEAD request (like GET)
              $method_match = TRUE;
            }
          }

          if (NULL === $method_match) {
            $method_match = FALSE;
          }
        }
        elseif (NULL !== $method && strcasecmp($req_method, $method) !== 0) {
          $method_match = FALSE;

          // Test for HEAD request (like GET)
          if (
            strcasecmp($req_method, 'HEAD') === 0
            && (strcasecmp($method, 'HEAD') === 0 || strcasecmp($method, 'GET') === 0)
          ) {
            $method_match = TRUE;
          }
        }
        elseif (NULL !== $method && strcasecmp($req_method, $method) === 0) {
          $method_match = TRUE;
        }

        // If the method was matched or if it wasn't even passed (in the route
        // callback).
        $possible_match = (NULL === $method_match) || $method_match;

        // ! is used to negate a match
        if (isset($path[0]) && $path[0] === '!') {
          $negate = TRUE;
          $i = 1;
        }
        else {
          $negate = FALSE;
          $i = 0;
        }

        // Check for a wildcard (match all)
        if ($path === '*') {
          $match = TRUE;
        }
        elseif (
          ($path === '404' && $matched->isEmpty() && count($methods_matched) <= 0)
          || ($path === '405' && $matched->isEmpty() && count($methods_matched) > 0)
        ) {
          // Warn user of deprecation.
          @trigger_error(
            // phpcs:disable
            'Use of 404/405 "routes" is deprecated. Use $klein->onHttpError() instead.',
            // phpcs:enable
            E_USER_DEPRECATED
          );
          // @todo Possibly remove in future, here for backwards compatibility.
          $this->onHttpError($route);

          continue;
        }
        elseif (isset($path[$i]) && $path[$i] === '@') {
          // @ is used to specify custom regex.
          $match = preg_match('`' . substr($path, $i + 1) . '`', $uri, $params);
        }
        else {
          // Compiling and matching regular expressions is relatively
          // expensive, so try and match by a substring first.
          $expression = '';
          $regex = FALSE;
          $j = 0;
          $n = $path[$i] ?? NULL;

          // Find the longest non-regex substring and match it against the URI.
          while (TRUE) {
            if (!isset($path[$i])) {
              break;
            }
            elseif (FALSE === $regex) {
              $c = $n;
              $regex = $c === '[' || $c === '(' || $c === '.';
              if (FALSE === $regex && isset($path[$i + 1])) {
                $n = $path[$i + 1];
                $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
              }
              if (FALSE === $regex && $c !== '/' && (!isset($uri[$j]) || $c !== $uri[$j])) {
                continue 2;
              }
              $j++;
            }
            $expression .= $path[$i++];
          }

          try {
            // Check if there's a cached regex string.
            if (FALSE !== $apc) {
              $regex = apc_fetch("route:$expression");
              if (FALSE === $regex) {
                $regex = $this->compileRoute($expression);
                apc_store("route:$expression", $regex);
              }
            }
            else {
              $regex = $this->compileRoute($expression);
            }
          }
          catch (RegularExpressionCompilationException $exception) {
            throw RoutePathCompilationException::createFromRoute($route, $exception);
          }

          if (is_string($regex)) {
            $match = preg_match($regex, $uri, $params);
          }
        }

        if ($match ^ $negate) {
          if ($possible_match) {
            if (!empty($params)) {
              // URL Decode the params according to RFC 3986.
              //
              // @link http://www.faqs.org/rfcs/rfc3986
              //
              // Decode here AFTER matching as per @chriso's suggestion
              //
              // @link https://github.com/klein/klein.php/issues/117#issuecomment-21093915
              $params = array_map('rawurldecode', $params);

              $this->request->paramsNamed()->merge($params);
            }

            // Handle our response callback.
            try {
              $this->handleRouteCallback($route, $matched, $methods_matched);
            }
            catch (DispatchHaltedException $exception) {
              switch ($exception->getCode()) {
                case DispatchHaltedException::SKIP_THIS:
                  continue 2;

                case DispatchHaltedException::SKIP_NEXT:
                  $skip_num = $exception->getNumberOfSkips();
                  break;

                case DispatchHaltedException::SKIP_REMAINING:
                  break 2;

                default:
                  throw $exception;
              }
            }

            if ($path !== '*') {
              $count_match && $matched->add($route);
            }
          }

          // Don't bother counting this as a method match if the route isn't
          // supposed to match anyway.
          if ($count_match) {
            // Keep track of possibly matched methods.
            $methods_matched = array_merge($methods_matched, (array) $method);
            $methods_matched = array_filter($methods_matched);
            $methods_matched = array_unique($methods_matched);
          }
        }
      }

      // Handle our 404/405 conditions.
      if ($matched->isEmpty() && count($methods_matched) > 0) {
        // Add our methods to our allow header.
        $this->response->header('Allow', implode(', ', $methods_matched));

        if (strcasecmp($req_method, 'OPTIONS') !== 0) {
          throw HttpException::createFromCode(405);
        }
      }
      elseif ($matched->isEmpty()) {
        throw HttpException::createFromCode(404);
      }
    }
    catch (HttpExceptionInterface $exception) {
      // Grab our original response lock state.
      $locked = $this->response->isLocked();

      // Call our http error handlers.
      $this->httpError($exception, $matched, $methods_matched);

      // Make sure we return our response to its original lock state.
      if (!$locked) {
        $this->response->unlock();
      }
    }
    catch (\Throwable $exception) {
      $this->error($exception);
    }

    try {
      if ($this->response->chunked) {
        $this->response->chunk();
      }
      else {
        // Output capturing behavior.
        switch ($capture) {
          case self::DISPATCH_CAPTURE_AND_RETURN:
            $buffed_content = '';
            while (ob_get_level() >= $this->outputBufferLevel) {
              $buffed_content = ob_get_clean() ?: '';
            }
            return $buffed_content;

          case self::DISPATCH_CAPTURE_AND_REPLACE:
            while (ob_get_level() >= $this->outputBufferLevel) {
              $this->response->body(ob_get_clean() ?: NULL);
            }
            break;

          case self::DISPATCH_CAPTURE_AND_PREPEND:
            while (ob_get_level() >= $this->outputBufferLevel) {
              $this->response->prepend(ob_get_clean() ?: '');
            }
            break;

          case self::DISPATCH_CAPTURE_AND_APPEND:
            while (ob_get_level() >= $this->outputBufferLevel) {
              $this->response->append(ob_get_clean() ?: '');
            }
            break;

          default:
            // If not a handled capture strategy, default to no capture.
            $capture = self::DISPATCH_NO_CAPTURE;
        }
      }

      // Test for HEAD request (like GET).
      if (strcasecmp($req_method, 'HEAD') === 0) {
        // HEAD requests shouldn't return a body.
        $this->response->body('');

        while (ob_get_level() >= $this->outputBufferLevel) {
          ob_end_clean();
        }
      }
      elseif (self::DISPATCH_NO_CAPTURE === $capture) {
        while (ob_get_level() >= $this->outputBufferLevel) {
          ob_end_flush();
        }
      }
    }
    catch (LockedResponseException $exception) {
      // Do nothing, since this is an automated behavior.
    }

    // Run our after dispatch callbacks.
    $this->callAfterDispatchCallbacks();

    if ($send_response && !$this->response->isSent()) {
      $this->response->send();
    }

    return '';
  }

  /**
   * Compiles a route string to a regular expression.
   *
   * @param string $route
   *   The route string to compile.
   *
   * @return string
   *   Route regular expression.
   */
  protected function compileRoute(string $route): string {
    // First escape all of the non-named param (non [block]s) for regex-chars.
    $route = preg_replace_callback(
      static::ROUTE_ESCAPE_REGEX,
      function ($match) {
        return preg_quote($match[0]);
      },
      $route
    );

    // Get a local reference of the match types to pass into our closure.
    $match_types = $this->matchTypes;

    // Now let's actually compile the path.
    $route = preg_replace_callback(
      static::ROUTE_COMPILE_REGEX,
      function ($match) use ($match_types) {
        [, $pre, $type, $param, $optional] = $match;

        if (isset($match_types[$type])) {
          $type = $match_types[$type];
        }

        // Older versions of PCRE require the 'P' in (?P<named>)
        $pattern = '(?:'
             . ($pre !== '' ? $pre : NULL)
             . '('
             . ($param !== '' ? "?P<$param>" : NULL)
             . $type
             . '))'
             . ($optional !== '' ? '?' : NULL);

        return $pattern;
      },
      $route ?: ''
    );

    $regex = "`^$route$`";

    // Check if our regular expression is valid.
    $this->validateRegularExpression($regex);

    return $regex;
  }

  /**
   * Validate a regular expression.
   *
   * This simply checks if the regular expression is able to be compiled
   * and converts any warnings or notices in the compilation to an \Exception.
   *
   * @param string $regex
   *   The regular expression to validate.
   *
   * @return bool
   *   TRUE if valid.
   *
   * @throws \Klein\Exceptions\RegularExpressionCompilationException
   *   If the expression can't be compiled.
   */
  private function validateRegularExpression(string $regex): bool {
    $error_string = NULL;

    $handler = function ($errno, $errstr) use (&$error_string) {
      $error_string = $errstr;
      return TRUE;
    };

    // Set an error handler temporarily.
    set_error_handler(
      $handler,
      E_NOTICE | E_WARNING
    );

    if (FALSE === preg_match($regex, '') || !empty($error_string)) {
      // Remove our temporary error handler.
      restore_error_handler();

      throw new RegularExpressionCompilationException(
        $error_string ?? '',
        preg_last_error()
      );
    }

    // Remove our temporary error handler.
    restore_error_handler();

    return TRUE;
  }

  /**
   * Get the path for a given route.
   *
   * This looks up the route by its passed name and returns
   * the path/url for that route, with its URL params as
   * placeholders unless you pass a valid key-value pair array
   * of the placeholder params and their values.
   *
   * If a pathname is a complex/custom regular expression, this
   * method will simply return the regular expression used to
   * match the request pathname, unless an optional bool is
   * passed "flatten_regex" which will flatten the regular
   * expression into a simple path string.
   *
   * This method, and its style of reverse-compilation, was originally
   * inspired by a similar effort by Gilles Bouthenot (@gbouthenot)
   *
   * @param string $route_name
   *   The name of the route.
   * @param mixed[] $params
   *   The array of placeholder fillers.
   * @param bool $flatten_regex
   *   Optionally flatten custom regular expressions to "/".
   *
   * @return string
   *   Path.
   *
   * @throws \OutOfBoundsException
   *   If the route requested doesn't exist.
   * @throws \RuntimeException
   *   If the path could not be retrieved.
   *
   * @link https://github.com/gbouthenot
   */
  public function getPathFor(string $route_name, ?array $params = NULL, bool $flatten_regex = TRUE): string {
    // First, grab the route.
    $route = $this->routes->get($route_name);

    // Make sure we are getting a valid route.
    if (NULL === $route || !($route instanceof Route)) {
      throw new \OutOfBoundsException('No such route with name: ' . $route_name);
    }

    $path = $route->getPath();

    // Use our compilation regex to reverse the path's compilation from its
    // definition.
    $reversed_path = preg_replace_callback(
      static::ROUTE_COMPILE_REGEX,
      function ($match) use ($params) {
        [$block, $pre, , $param, $optional] = $match;

        if (isset($params[$param])) {
          $p = $params[$param];
          $pStr = is_scalar($p) ? (string) $p : '';
          return $pre . $pStr;
        }
        elseif ($optional) {
          return '';
        }

        return $block;
      },
      $path
    );

    if ($reversed_path === NULL) {
      throw new \RuntimeException('Error while compiling route path');
    }

    // If the path and reversed_path are the same, the regex must have not
    // matched/replaced.
    if ($path === $reversed_path && $flatten_regex && strpos($path, '@') === 0) {
      // If the path is a custom regular expression and we're "flattening",
      // just return a slash.
      $path = '/';
    }
    else {
      $path = $reversed_path;
    }

    return $path;
  }

  /**
   * Handle a route's callback.
   *
   * This handles common \Exceptions and their output
   * to keep the "dispatch()" method DRY.
   *
   * @param \Klein\Route $route
   *   Route.
   * @param \Klein\DataCollection\RouteCollection $matched
   *   Matched routes.
   * @param mixed[] $methods_matched
   *   Matches methods.
   */
  protected function handleRouteCallback(Route $route, RouteCollection $matched, array $methods_matched): void {
    // Handle the callback.
    $returned = call_user_func(
      // Instead of relying on the slower "invoke" magic.
      $route->getCallback(),
      $this->request,
      $this->response,
      $this->service,
      $this->app,
      // Pass the Klein instance.
      $this,
      $matched,
      $methods_matched
    );

    if ($returned instanceof AbstractResponse) {
      $this->response = $returned;
    }
    else {
      // Otherwise, attempt to append the returned data.
      try {
        $this->response->append((string) ($returned ?? ''));
      }
      catch (LockedResponseException $exception) {
        // Do nothing, since this is an automated behavior.
      }
    }
  }

  /**
   * Adds an error callback to the stack of error handlers.
   *
   * @param callable|string $callback
   *   The callable function to execute in the error handling chain.
   */
  public function onError(callable|string $callback): void {
    $this->errorCallbacks->push($callback);
  }

  /**
   * Routes an \Exception through the error callbacks.
   *
   * @param \Throwable $error
   *   The exception that occurred.
   *
   * @throws \Klein\Exceptions\UnhandledException
   *   If the error/exception isn't handled by an error callback.
   */
  protected function error(\Throwable $error): void {
    $type = get_class($error);
    $message = $error->getMessage();

    try {
      if (!$this->errorCallbacks->isEmpty()) {
        foreach ($this->errorCallbacks as $callback) {
          if (is_callable($callback)) {
            if (is_string($callback)) {
              $callback($this, $message, $type, $error);

              return;
            }
            else {
              call_user_func($callback, $this, $message, $type, $error);

              return;
            }
          }
          else {
            $this->service->flash((string) $error);
            $this->response->redirect($callback);
          }
        }
      }
      else {
        $this->response->code(500);

        while (ob_get_level() >= $this->outputBufferLevel) {
          ob_end_clean();
        }

        throw new UnhandledException($message, $error->getCode(), $error);
      }
    }
    catch (\Throwable $exception) {
      // Make sure to clean the output buffer before bailing.
      while (ob_get_level() >= $this->outputBufferLevel) {
        ob_end_clean();
      }

      throw $exception;
    }

    // Lock our response, since we probably don't want anything else messing
    // with our error code/body.
    $this->response->lock();
  }

  /**
   * Adds an HTTP error callback to the stack of HTTP error handlers.
   *
   * @param callable|\Klein\Route $callback
   *   The callable function to execute in the error handling chain.
   */
  public function onHttpError(callable|Route $callback): void {
    $this->httpErrorCallbacks->push($callback);
  }

  /**
   * Handles an HTTP error \Exception through our HTTP error callbacks.
   *
   * @param \Klein\Exceptions\HttpExceptionInterface $http_exception
   *   The exception that occurred.
   * @param \Klein\DataCollection\RouteCollection $matched
   *   The collection of routes that were matched in dispatch.
   * @param mixed[] $methods_matched
   *   The HTTP methods that were matched in dispatch.
   */
  protected function httpError(
    HttpExceptionInterface $http_exception,
    RouteCollection $matched,
    array $methods_matched,
  ): void {
    if (!$this->response->isLocked()) {
      $this->response->code($http_exception->getCode());
    }

    if (!$this->httpErrorCallbacks->isEmpty()) {
      foreach ($this->httpErrorCallbacks as $callback) {
        if ($callback instanceof Route) {
          $this->handleRouteCallback($callback, $matched, $methods_matched);
        }
        elseif (is_callable($callback)) {
          if (is_string($callback)) {
            $callback(
              $http_exception->getCode(),
              $this,
              $matched,
              $methods_matched,
              $http_exception
            );
          }
          else {
            call_user_func(
              $callback,
              $http_exception->getCode(),
              $this,
              $matched,
              $methods_matched,
              $http_exception
            );
          }
        }
      }
    }

    // Lock our response, since we probably don't want anything else messing
    // with our error code/body.
    $this->response->lock();
  }

  /**
   * Queue after dispatch callback.
   *
   * Adds a callback to the stack of handlers to run after the dispatch
   * loop has handled all of the route callbacks and before the response
   * is sent.
   *
   * @param callable $callback
   *   The callable function to execute in the after route chain.
   */
  public function afterDispatch(callable $callback): void {
    $this->afterFilterCallbacks->enqueue($callback);
  }

  /**
   * Runs through and executes the after dispatch callbacks.
   *
   * @see ::error()
   */
  protected function callAfterDispatchCallbacks(): void {
    try {
      foreach ($this->afterFilterCallbacks as $callback) {
        if (is_callable($callback)) {
          if (is_string($callback)) {
            $callback($this);
          }
          else {
            call_user_func($callback, $this);
          }
        }
      }
    }
    catch (\Throwable $exception) {
      $this->error($exception);
    }
  }

  /**
   * Method aliases.
   */

  /**
   * Quick alias to skip the current callback/route method from executing.
   *
   * @throws \Klein\Exceptions\DispatchHaltedException
   *   To halt/skip the current dispatch loop.
   */
  public function skipThis(): void {
    throw new DispatchHaltedException('', DispatchHaltedException::SKIP_THIS);
  }

  /**
   * Quick alias to skip the next callback/route method from executing.
   *
   * @param int $num
   *   The number of next matches to skip.
   *
   * @throws \Klein\Exceptions\DispatchHaltedException
   *   To halt/skip the current dispatch loop.
   */
  public function skipNext($num = 1): void {
    $skip = new DispatchHaltedException('', DispatchHaltedException::SKIP_NEXT);
    $skip->setNumberOfSkips($num);

    throw $skip;
  }

  /**
   * Quick alias to stop the remaining callbacks/route methods from executing.
   *
   * @throws \Klein\Exceptions\DispatchHaltedException
   *   To halt/skip the current dispatch loop.
   */
  public function skipRemaining(): void {
    throw new DispatchHaltedException('', DispatchHaltedException::SKIP_REMAINING);
  }

  /**
   * Abort a response.
   *
   * Alias to set a response code, lock the response, and halt the route
   * matching/dispatching.
   *
   * @param ?int $code
   *   Optional HTTP status code to send.
   *
   * @throws \Klein\Exceptions\DispatchHaltedException
   *   To halt/skip the current dispatch loop.
   */
  public function abort(?int $code = NULL): void {
    if (NULL !== $code) {
      throw HttpException::createFromCode($code);
    }

    throw new DispatchHaltedException();
  }

  /**
   * OPTIONS alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function options(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('OPTIONS', $args['path'], $args['callback']);
  }

  /**
   * HEAD alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function head(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('HEAD', $args['path'], $args['callback']);
  }

  /**
   * GET alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function get(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('GET', $args['path'], $args['callback']);
  }

  /**
   * POST alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function post(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('POST', $args['path'], $args['callback']);
  }

  /**
   * PUT alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function put(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('PUT', $args['path'], $args['callback']);
  }

  /**
   * DELETE alias for "respond()".
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   */
  public function delete(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('DELETE', $args['path'], $args['callback']);
  }

  /**
   * PATCH alias for "respond()".
   *
   * PATCH was added to HTTP/1.1 in RFC5789.
   *
   * @return \Klein\Route
   *   The route.
   *
   * @see ::respond()
   * @link http://tools.ietf.org/html/rfc5789
   */
  public function patch(): Route {
    $args = $this->parseLooseArgumentOrder(func_get_args());
    return $this->respond('PATCH', $args['path'], $args['callback']);
  }

}
