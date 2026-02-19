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

/**
 * Http Status.
 *
 * HTTP status code and message translator.
 */
class HttpStatus {

  /**
   * The HTTP status code.
   *
   * @var int
   */
  protected int $code;

  /**
   * The HTTP status message.
   *
   * @var string
   */
  protected string $message;

  /**
   * HTTP 1.1 status messages based on code.
   *
   * @var array<int, string>
   *
   * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
   */
  protected static $httpMessages = [
    // Informational 1xx.
    100 => 'Continue',
    101 => 'Switching Protocols',

    // Successful 2xx.
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',

    // Redirection 3xx.
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => '(Unused)',
    307 => 'Temporary Redirect',

    // Client Error 4xx.
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',

    // Server Error 5xx.
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
  ];

  /**
   * Constructor.
   *
   * @param int $code
   *   The HTTP code.
   * @param ?string $message
   *   Optional HTTP message for the corresponding code.
   */
  public function __construct(int $code, ?string $message = NULL) {
    $this->setCode($code);

    if (NULL === $message) {
      $message = static::getMessageFromCode($code);
    }

    $this->message = $message;
  }

  /**
   * Get the HTTP status code.
   *
   * @return int
   *   Code.
   */
  public function getCode(): int {
    return $this->code;
  }

  /**
   * Get the HTTP status message.
   *
   * @return string
   *   Message.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Set the HTTP status code.
   *
   * @param int $code
   *   Code.
   *
   * @return static
   *   This object.
   */
  public function setCode(int $code): static {
    $this->code = (int) $code;
    return $this;
  }

  /**
   * Set the HTTP status message.
   *
   * @param string $message
   *   Message.
   *
   * @return static
   *   This object.
   */
  public function setMessage(string $message): static {
    $this->message = (string) $message;
    return $this;
  }

  /**
   * Get a string representation of our HTTP status.
   *
   * @return string
   *   Stringified HTTP status.
   */
  public function getFormattedString(): string {
    $string = (string) $this->code;
    $string = $string . ' ' . $this->message;

    return $string;
  }

  /**
   * Magic "__toString" method.
   *
   * Allows the ability to arbitrarily use an instance of this class as a string
   * This method will be automatically called, returning a string representation
   * of this instance.
   *
   * @return string
   *   Stringified HTTP status.
   */
  public function __toString(): string {
    return $this->getFormattedString();
  }

  /**
   * Get our HTTP 1.1 message from our passed code.
   *
   * Returns null if no corresponding message was found for the passed in code.
   *
   * @param int $code
   *   Code.
   *
   * @return string
   *   Message.
   */
  public static function getMessageFromCode($code): string {
    if (isset(static::$httpMessages[$code])) {
      return static::$httpMessages[$code];
    }
    else {
      return '';
    }
  }

}
