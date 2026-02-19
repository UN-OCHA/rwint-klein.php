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

/**
 * ServiceProvider.
 *
 * Service provider class for handling logic extending between
 * a request's data and a response's behavior.
 */
class ServiceProvider {

  /**
   * Class properties.
   */

  /**
   * The Request instance containing HTTP request data and behaviors.
   *
   * @var ?\Klein\Request
   */
  protected ?Request $request;

  /**
   * The Response instance containing HTTP response data and behaviors.
   *
   * @var ?\Klein\AbstractResponse
   */
  protected ?AbstractResponse $response;

  /**
   * The id of the current PHP session.
   *
   * @var string|false
   */
  protected string|false $sessionId = FALSE;

  /**
   * The view layout.
   *
   * @var ?string
   */
  protected ?string $layout;

  /**
   * The view to render.
   *
   * @var string
   */
  protected string $view = '';

  /**
   * Shared data collection.
   *
   * @var \Klein\DataCollection\DataCollection
   */
  protected DataCollection $sharedData;


  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param ?\Klein\Request $request
   *   Object containing all HTTP request data and behaviors.
   * @param ?\Klein\AbstractResponse $response
   *   Object containing all HTTP response data and behaviors.
   */
  public function __construct(?Request $request = NULL, ?AbstractResponse $response = NULL) {
    // Bind our objects.
    $this->bind($request, $response);

    // Instantiate our shared data collection.
    $this->sharedData = new DataCollection();
  }

  /**
   * Bind object instances to this service.
   *
   * @param \Klein\Request $request
   *   Object containing all HTTP request data and behaviors.
   * @param \Klein\AbstractResponse $response
   *   Object containing all HTTP response data and behaviors.
   *
   * @return static
   *   This object.
   */
  public function bind(?Request $request = NULL, ?AbstractResponse $response = NULL): static {
    // Keep references.
    $this->request = $request ?? $this->request ?? NULL;
    $this->response = $response ?? $this->response ?? NULL;

    return $this;
  }

  /**
   * Returns the shared data collection object.
   *
   * @return \Klein\DataCollection\DataCollection
   *   Shared data.
   */
  public function sharedData(): DataCollection {
    return $this->sharedData;
  }

  /**
   * Get the current session's ID.
   *
   * This will start a session if the current session id is null.
   *
   * @return string|false
   *   Session ID.
   */
  public function startSession(): string|false {
    $session_id = session_id();
    if (empty($session_id)) {
      // Attempt to start a session.
      if (session_start()) {
        $this->sessionId = session_id();
      }
      else {
        $this->sessionId = FALSE;
      }
    }

    return $this->sessionId ?: FALSE;
  }

  /**
   * Stores a flash message of $type.
   *
   * @param string $msg
   *   The message to flash.
   * @param string|array<string|int> $type
   *   The flash message type.
   * @param ?array<string|int> $params
   *   Optional params to be parsed by markdown.
   */
  public function flash(string $msg, string|array $type = 'info', ?array $params = NULL): void {
    $this->startSession();
    if (is_array($type)) {
      $params = $type;
      $type = 'info';
    }
    /** @var array<string, array<int, string>> $flashes */
    $flashes = isset($_SESSION['__flashes']) && is_array($_SESSION['__flashes']) ? $_SESSION['__flashes'] : [];
    if (!isset($flashes[$type])) {
      $flashes[$type] = [];
    }
    $flashes[$type][] = $this->markdown($msg, $params);
    $_SESSION['__flashes'] = $flashes;
  }

  /**
   * Returns and clears all flashes of optional $type.
   *
   * @param ?string $type
   *   The name of the flash message type.
   *
   * @return mixed[]
   *   Flashes.
   */
  public function flashes(?string $type = NULL): array {
    $this->startSession();

    if (!isset($_SESSION['__flashes']) || !is_array($_SESSION['__flashes'])) {
      return [];
    }

    /** @var array<string, array<int, mixed>> $sessionFlashes */
    $sessionFlashes = $_SESSION['__flashes'];

    if (NULL === $type) {
      $flashes = $sessionFlashes;
      unset($_SESSION['__flashes']);
      return $flashes;
    }

    $flashes = $sessionFlashes[$type] ?? [];
    unset($sessionFlashes[$type]);
    $_SESSION['__flashes'] = $sessionFlashes;

    return $flashes;
  }

  /**
   * Render a text string as markdown.
   *
   * Supports basic markdown syntax.
   *
   * Also, this method takes in EITHER an array of optional arguments (as
   * the second parameter) ... OR this method will simply take a variable number
   * of arguments (after the initial string arg).
   *
   * @return string
   *   Markdown string.
   *
   * @see ::doMarkdown()
   */
  public static function markdown(): string {
    $args = func_get_args();
    $string = array_shift($args);
    if (!is_string($string)) {
      return '';
    }
    return static::doMarkdown($string, $args);
  }

  /**
   * Render a text string as markdown.
   *
   * Supports basic markdown syntax.
   *
   * @param string $string
   *   String to convert to markdown.
   * @param mixed[] $args
   *   Arguments to replace in the string.
   *
   * @return string
   *   Markdown string.
   */
  public static function doMarkdown(string $string, array $args) {
    // Create our markdown parse/conversion regex's.
    $md = [
      '/\[([^\]]++)\]\(([^\)]++)\)/' => '<a href="$2">$1</a>',
      '/\*\*([^\*]++)\*\*/' => '<strong>$1</strong>',
      '/\*([^\*]++)\*/' => '<em>$1</em>',
    ];

    if (isset($args[0]) && is_array($args[0])) {
      // If our "second" argument (now the first array item is an array)
      // just use the array as the arguments and forget the rest.
      $args = $args[0];
    }

    // Encode our args so we can insert them into an HTML string.
    $values = [];
    foreach ($args as $arg) {
      if (is_scalar($arg)) {
        $values[] = htmlentities((string) $arg, ENT_QUOTES, 'UTF-8');
      }
    }

    // Actually do our markdown conversion.
    $content = preg_replace(array_keys($md), $md, $string);
    return isset($content) ? vsprintf($content, $values) : '';
  }

  /**
   * Escapes a string for UTF-8 HTML displaying.
   *
   * This is a quick macro for escaping strings designed
   * to be shown in a UTF-8 HTML environment. Its options
   * are otherwise limited by design.
   *
   * @param string $string
   *   The string to escape.
   * @param int $flags
   *   A bitmask of `htmlentities()` compatible flags.
   *
   * @return string
   *   Escaped string.
   */
  public static function escape(string $string, int $flags = ENT_QUOTES): string {
    return htmlentities($string, $flags, 'UTF-8');
  }

  /**
   * Redirects the request to the current URL.
   *
   * @return static
   *   This object.
   */
  public function refresh(): static {
    if (isset($this->response, $this->request)) {
      $this->response->redirect(
        $this->request->uri()
      );
    }

    return $this;
  }

  /**
   * Redirects the request back to the referrer.
   *
   * @return static
   *   This object.
   */
  public function back(): static {
    if (isset($this->response, $this->request)) {
      $referer = $this->request->server()->get('HTTP_REFERER', NULL);
      $referer = is_string($referer) ? $referer : NULL;

      if (NULL !== $referer) {
        $this->response->redirect($referer);
      }
      else {
        $this->refresh();
      }
    }

    return $this;
  }

  /**
   * Get (or set) the view's layout.
   *
   * Simply calling this method without any arguments returns the current
   * layout. Calling with an argument, however, sets the layout to what was
   * provided by the argument.
   *
   * @param string $layout
   *   The layout of the view.
   *
   * @return static|string|null
   *   View layout or this object if used to set the layout.
   */
  public function layout(?string $layout = NULL): static|string|null {
    if (NULL !== $layout) {
      $this->layout = $layout;

      return $this;
    }

    return $this->layout ?? NULL;
  }

  /**
   * Renders the current view.
   */
  public function yieldView(): void {
    include $this->view;
  }

  /**
   * Renders a view + optional layout.
   *
   * @param string $view
   *   The view to render.
   * @param mixed[] $data
   *   The data to render in the view.
   */
  public function render(string $view, array $data = []): void {
    $original_view = $this->view;

    if (!empty($data)) {
      $this->sharedData->merge($data);
    }

    $this->view = $view;

    if (!isset($this->layout)) {
      $this->yieldView();
    }
    else {
      include $this->layout;
    }

    if (isset($this->response) && FALSE !== $this->response->chunked) {
      $this->response->chunk();
    }

    // Restore state for parent render().
    $this->view = $original_view;
  }

  /**
   * Renders a view without a layout.
   *
   * @param string $view
   *   The view to render.
   * @param mixed[] $data
   *   The data to render in the view.
   */
  public function partial(string $view, array $data = []): void {
    $layout = $this->layout ?? NULL;
    $this->layout = NULL;
    $this->render($view, $data);
    $this->layout = $layout;
  }

  /**
   * Add a custom validator for our validation method.
   *
   * @param string $method
   *   The name of the validator method.
   * @param callable $callback
   *   The callback to perform on validation.
   */
  public function addValidator(string $method, callable $callback): void {
    Validator::addValidator($method, $callback);
  }

  /**
   * Start a validator chain for the specified string.
   *
   * @param string $string
   *   The string to validate.
   * @param string|false|null $error
   *   The custom exception message to throw.
   *
   * @return \Klein\Validator
   *   Validator.
   */
  public function validate(string $string, string|false|null $error = NULL): Validator {
    return new Validator($string, $error);
  }

  /**
   * Start a validator chain for the specified parameter.
   *
   * @param string $param
   *   The name of the parameter to validate.
   * @param string|false|null $error
   *   The custom exception message to throw.
   *
   * @return \Klein\Validator
   *   Validator.
   */
  public function validateParam(string $param, string|false|null $error = NULL): Validator {
    $string = isset($this->request) ? $this->request->param($param) : '';
    return $this->validate(is_string($string) ? $string : '', $error);
  }

  /**
   * Returns the request object.
   *
   * @return ?\Klein\Request
   *   Request.
   */
  public function getRequest(): ?Request {
    return $this->request;
  }

  /**
   * Returns the response object.
   *
   * @return ?\Klein\AbstractResponse
   *   Response.
   */
  public function getResponse(): ?AbstractResponse {
    return $this->response;
  }

  /**
   * Magic "__isset" method.
   *
   * Allows the ability to arbitrarily check the existence of shared data
   * from this instance while treating it as an instance property.
   *
   * @param string $key
   *   The name of the shared data.
   *
   * @return bool
   *   TRUE if set.
   */
  public function __isset(string $key): bool {
    return $this->sharedData->exists($key);
  }

  /**
   * Magic "__get" method.
   *
   * Allows the ability to arbitrarily request shared data from this instance
   * while treating it as an instance property.
   *
   * @param string $key
   *   The name of the shared data.
   *
   * @return mixed
   *   The value of the shared data.
   */
  public function __get(string $key): mixed {
    return $this->sharedData->get($key);
  }

  /**
   * Magic "__set" method.
   *
   * Allows the ability to arbitrarily set shared data from this instance
   * while treating it as an instance property.
   *
   * @param string $key
   *   The name of the shared data.
   * @param mixed $value
   *   The value of the shared data.
   */
  public function __set(string $key, mixed $value): void {
    $this->sharedData->set($key, $value);
  }

  /**
   * Magic "__unset" method.
   *
   * Allows the ability to arbitrarily remove shared data from this instance
   * while treating it as an instance property.
   *
   * @param string $key
   *   The name of the shared data.
   */
  public function __unset(string $key): void {
    $this->sharedData->remove($key);
  }

}
