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

use Klein\DataCollection\HeaderDataCollection;
use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\Exceptions\LockedResponseException;
use Klein\Exceptions\ResponseAlreadySentException;

/**
 * Abstract Response.
 */
abstract class AbstractResponse {

  /**
   * Properties.
   */

  /**
   * The default response HTTP status code.
   *
   * @var int
   */
  protected static int $defaultStatusCode = 200;

  /**
   * The HTTP version of the response.
   *
   * @var string
   */
  protected string $protocolVersion = '1.1';

  /**
   * The response body.
   *
   * @var string
   */
  protected string $body = '';

  /**
   * HTTP response status.
   *
   * @var \Klein\HttpStatus
   */
  protected HttpStatus $status;

  /**
   * HTTP response headers.
   *
   * @var \Klein\DataCollection\HeaderDataCollection
   */
  protected HeaderDataCollection $headers;

  /**
   * HTTP response cookies.
   *
   * @var \Klein\DataCollection\ResponseCookieDataCollection
   */
  protected ResponseCookieDataCollection $cookies;

  /**
   * Whether or not the response is "locked" from any further modification.
   *
   * @var bool
   */
  protected bool $locked = FALSE;

  /**
   * Whether or not the response has been sent.
   *
   * @var bool
   */
  protected bool $sent = FALSE;

  /**
   * Whether the response has been chunked or not.
   *
   * @var bool
   */
  public bool $chunked = FALSE;


  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * Create a new AbstractResponse object with a dependency injected Headers
   * instance.
   *
   * @param string $body
   *   The response body's content.
   * @param ?int $status_code
   *   The status code.
   * @param mixed[] $headers
   *   The response header "hash".
   */
  public function __construct(?string $body = '', ?int $status_code = NULL, array $headers = []) {
    $status_code = $status_code ?: static::$defaultStatusCode;

    // Set our body and code using our internal methods.
    $this->body($body);
    $this->code($status_code);

    $this->headers = new HeaderDataCollection($headers);
    $this->cookies = new ResponseCookieDataCollection();
  }

  /**
   * Get (or set) the HTTP protocol version.
   *
   * Simply calling this method without any arguments returns the current
   * protocol version. Calling with an integer argument, however, attempts to
   * set the protocol version to what was provided by the argument.
   *
   * @param ?string $protocol_version
   *   Protocal version.
   *
   * @return string|static
   *   Protocal version or this object if $protocol_version is defined.
   */
  public function protocolVersion(?string $protocol_version = NULL): string|static {
    if (NULL !== $protocol_version) {
      // Require that the response be unlocked before changing it.
      $this->requireUnlocked();

      $this->protocolVersion = (string) $protocol_version;

      return $this;
    }

    return $this->protocolVersion;
  }

  /**
   * Get (or set) the response's body content.
   *
   * Simply calling this method without any arguments returns the current
   * response body. Calling with an argument, however, sets the response body
   * to what was provided by the argument.
   *
   * @param ?string $body
   *   The body content string.
   *
   * @return string|static
   *   The response body or this object if $body is NULL.
   */
  public function body(?string $body = NULL): string|static {
    if (NULL !== $body) {
      // Require that the response be unlocked before changing it.
      $this->requireUnlocked();

      $this->body = (string) $body;

      return $this;
    }

    return $this->body;
  }

  /**
   * Returns the status object.
   *
   * @return \Klein\HttpStatus
   *   Status object.
   */
  public function status(): HttpStatus {
    return $this->status;
  }

  /**
   * Returns the headers collection.
   *
   * @return \Klein\DataCollection\HeaderDataCollection
   *   Headers collection.
   */
  public function headers(): HeaderDataCollection {
    return $this->headers;
  }

  /**
   * Returns the cookies collection.
   *
   * @return \Klein\DataCollection\ResponseCookieDataCollection
   *   Cookies collection.
   */
  public function cookies(): ResponseCookieDataCollection {
    return $this->cookies;
  }

  /**
   * Get (or set) the HTTP response code.
   *
   * Simply calling this method without any arguments returns the current
   * response code. Calling with an integer argument, however, attempts to set
   * the response code to what was provided by the argument.
   *
   * @param ?int $code
   *   The HTTP status code to send.
   *
   * @return int|static
   *   The response code or this object if $code is NULL.
   */
  public function code(?int $code = NULL): int|static {
    if (NULL !== $code) {
      // Require that the response be unlocked before changing it.
      $this->requireUnlocked();

      $this->status = new HttpStatus($code);

      return $this;
    }

    return $this->status->getCode();
  }

  /**
   * Prepend a string to the response's content body.
   *
   * @param string $content
   *   The string to prepend.
   *
   * @return static
   *   This object.
   */
  public function prepend(string $content): static {
    // Require that the response be unlocked before changing it.
    $this->requireUnlocked();

    $this->body = $content . $this->body;

    return $this;
  }

  /**
   * Append a string to the response's content body.
   *
   * @param string $content
   *   The string to append.
   *
   * @return static
   *   This object.
   */
  public function append(string $content): static {
    // Require that the response be unlocked before changing it.
    $this->requireUnlocked();

    $this->body .= $content;

    return $this;
  }

  /**
   * Check if the response is locked.
   *
   * @return bool
   *   TRUE if locked.
   */
  public function isLocked(): bool {
    return $this->locked;
  }

  /**
   * Require that the response is unlocked.
   *
   * Throws an exception if the response is locked, preventing any methods from
   * mutating the response when its locked.
   *
   * @return static
   *   This object.
   *
   * @throws \Klein\Exceptions\LockedResponseException
   *    If the response is locked.
   */
  public function requireUnlocked(): static {
    if ($this->isLocked()) {
      throw new LockedResponseException('Response is locked');
    }

    return $this;
  }

  /**
   * Lock the response from further modification.
   *
   * @return static
   *   This object.
   */
  public function lock(): static {
    $this->locked = TRUE;

    return $this;
  }

  /**
   * Unlock the response from further modification.
   *
   * @return static
   *   This object.
   */
  public function unlock(): static {
    $this->locked = FALSE;

    return $this;
  }

  /**
   * Generates an HTTP compatible status header line string.
   *
   * Creates the string based off of the response's properties.
   *
   * @return string
   *   The HTTP status line.
   */
  protected function httpStatusLine(): string {
    return sprintf('HTTP/%s %s', $this->protocolVersion, $this->status);
  }

  /**
   * Send our HTTP headers.
   *
   * @param bool $cookies_also
   *   Whether or not to also send the cookies after sending the normal headers.
   * @param bool $override
   *   Whether or not to override the check if headers have already been sent.
   *
   * @return static
   *   This object.
   */
  public function sendHeaders(bool $cookies_also = TRUE, bool $override = FALSE): static {
    if (headers_sent() && !$override) {
      return $this;
    }

    // Send our HTTP status line.
    header($this->httpStatusLine());

    // Iterate through our Headers data collection and send each header.
    foreach ($this->headers as $key => $value) {
      $headerValue = is_scalar($value) ? (string) $value : '';
      header((string) $key . ': ' . $headerValue, FALSE);
    }

    if ($cookies_also) {
      $this->sendCookies($override);
    }

    return $this;
  }

  /**
   * Send our HTTP response cookies.
   *
   * @param bool $override
   *   Whether or not to override the check if headers have already been sent.
   *
   * @return static
   *   This object.
   */
  public function sendCookies(bool $override = FALSE): static {
    if (headers_sent() && !$override) {
      return $this;
    }

    // Iterate through our Cookies data collection and set each cookie natively.
    foreach ($this->cookies as $cookie) {
      if ($cookie instanceof ResponseCookie) {
        // Use the built-in PHP "setcookie" function.
        setcookie(
          $cookie->getName(),
          $cookie->getValue(),
          $cookie->getExpire(),
          $cookie->getPath(),
          $cookie->getDomain(),
          $cookie->getSecure(),
          $cookie->getHttpOnly()
        );
      }
    }
    return $this;
  }

  /**
   * Send our body's contents.
   *
   * @return static
   *   This object.
   */
  public function sendBody(): static {
    echo (string) $this->body;

    return $this;
  }

  /**
   * Send the response and lock it.
   *
   * @param bool $override
   *   Whether or not to override the check if the response has already been
   *   sent.
   *
   * @return static
   *   This object.
   *
   * @throws \Klein\Exceptions\ResponseAlreadySentException
   *   If the response has already been sent.
   */
  public function send(bool $override = FALSE): static {
    if ($this->sent && !$override) {
      throw new ResponseAlreadySentException('Response has already been sent');
    }

    // Send our response data.
    $this->sendHeaders();
    $this->sendBody();

    // Lock the response from further modification.
    $this->lock();

    // Mark as sent.
    $this->sent = TRUE;

    // If there running FPM, tell the process manager to finish the server
    // request/response handling.
    if (function_exists('fastcgi_finish_request')) {
      fastcgi_finish_request();
    }

    return $this;
  }

  /**
   * Check if the response has been sent.
   *
   * @return bool
   *   TRUE if the response has been sent.
   */
  public function isSent() {
    return $this->sent;
  }

  /**
   * Enable response chunking.
   *
   * @return static
   *   This object.
   *
   * @link https://github.com/klein/klein.php/wiki/Response-Chunking
   * @link http://bit.ly/hg3gHb
   */
  public function chunk(): static {
    if (FALSE === $this->chunked) {
      $this->chunked = TRUE;
      $this->header('Transfer-encoding', 'chunked');
      flush();
    }

    if (($body_length = strlen($this->body)) > 0) {
      printf("%x\r\n", $body_length);
      $this->sendBody();
      $this->body('');
      echo "\r\n";
      flush();
    }

    return $this;
  }

  /**
   * Sets a response header.
   *
   * @param string $key
   *   The name of the HTTP response header.
   * @param mixed $value
   *   The value to set the header with.
   *
   * @return static
   *   This object.
   */
  public function header(string $key, mixed $value): static {
    $this->headers->set($key, $value);

    return $this;
  }

  /**
   * Sets a response cookie.
   *
   * @param string $key
   *   The name of the cookie.
   * @param string $value
   *   The value to set the cookie with.
   * @param ?int $expiry
   *   The time that the cookie should expire.
   * @param string $path
   *   The path of which to restrict the cookie.
   * @param string $domain
   *   The domain of which to restrict the cookie.
   * @param bool $secure
   *   Flag of whether the cookie should only be sent over a HTTPS connection.
   * @param bool $httponly
   *   Flag of whether the cookie should only be accessible over the HTTP
   *   protocol.
   *
   * @return static
   *   This object.
   */
  public function cookie(
    string $key,
    string $value = '',
    ?int $expiry = NULL,
    string $path = '/',
    string $domain = '',
    bool $secure = FALSE,
    bool $httponly = FALSE,
  ): static {
    if (NULL === $expiry) {
      $expiry = time() + (3600 * 24 * 30);
    }

    $this->cookies->set(
      $key,
      new ResponseCookie($key, $value, $expiry, $path, $domain, $secure, $httponly)
    );

    return $this;
  }

  /**
   * Tell the browser not to cache the response.
   *
   * @return static
   *   This object.
   */
  public function noCache(): static {
    $this->header('Pragma', 'no-cache');
    $this->header('Cache-Control', 'no-store, no-cache');

    return $this;
  }

  /**
   * Redirects the request to another URL.
   *
   * @param string $url
   *   The URL to redirect to.
   * @param int $code
   *   The HTTP status code to use for redirection.
   *
   * @return static
   *   This object.
   */
  public function redirect(string $url, int $code = 302): static {
    $this->code($code);
    $this->header('Location', $url);
    $this->lock();

    return $this;
  }

}
