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

use Klein\Exceptions\ValidationException;

/**
 * Validator.
 *
 * Base validator class.
 */
class Validator {

  /**
   * Class properties.
   */

  /**
   * The available validator methods.
   *
   * @var mixed[]
   */
  public static array $methods = [];

  /**
   * The string to validate.
   *
   * @var string
   */
  protected string $string;

  /**
   * The custom exception message to throw on validation failure.
   *
   * @var string|false|null
   */
  protected string|false|null $error;

  /**
   * Flag for whether the default validation methods have been added or not.
   *
   * @var bool
   */
  protected static bool $defaultAdded = FALSE;

  /**
   * Methods.
   */

  /**
   * Sets up the validator chain with the string and optional error message.
   *
   * @param string $string
   *   The string to validate.
   * @param string|false|null $error
   *   The optional custom exception message to throw on validation failure.
   */
  public function __construct(string $string, string|false|null $error = NULL) {
    $this->string = $string;
    $this->error = $error;

    if (!static::$defaultAdded) {
      static::addDefault();
    }
  }

  /**
   * Adds default validators on first use.
   */
  public static function addDefault(): void {
    static::$methods['null'] = function ($string) {
      return $string === NULL || $string === '';
    };
    static::$methods['len'] = function ($string, $min, $max = NULL) {
      $len = strlen($string);
      return NULL === $max ? $len === $min : $len >= $min && $len <= $max;
    };
    static::$methods['int'] = function ($string) {
      return (string) $string === ((string) (int) $string);
    };
    static::$methods['float'] = function ($string) {
      return (string) $string === ((string) (float) $string);
    };
    static::$methods['email'] = function ($string) {
      return filter_var($string, FILTER_VALIDATE_EMAIL) !== FALSE;
    };
    static::$methods['url'] = function ($string) {
      return filter_var($string, FILTER_VALIDATE_URL) !== FALSE;
    };
    static::$methods['ip'] = function ($string) {
      return filter_var($string, FILTER_VALIDATE_IP) !== FALSE;
    };
    static::$methods['remoteip'] = function ($string) {
      return filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE;
    };
    static::$methods['alnum'] = function ($string) {
      return ctype_alnum($string);
    };
    static::$methods['alpha'] = function ($string) {
      return ctype_alpha($string);
    };
    static::$methods['contains'] = function ($string, $needle) {
      return strpos($string, $needle) !== FALSE;
    };
    static::$methods['regex'] = function ($string, $pattern) {
      return preg_match($pattern, $string);
    };
    static::$methods['chars'] = function ($string, $chars) {
      return preg_match("/^[$chars]++$/i", $string);
    };

    static::$defaultAdded = TRUE;
  }

  /**
   * Add a custom validator to our list of validation methods.
   *
   * @param string $method
   *   The name of the validator method.
   * @param callable $callback
   *   The callback to perform on validation.
   */
  public static function addValidator(string $method, callable $callback): void {
    static::$methods[strtolower($method)] = $callback;
  }

  /**
   * Magic "__call" method.
   *
   * Allows the ability to arbitrarily call a validator with an optional prefix
   * of "is" or "not" by simply calling an instance property like a callback.
   *
   * @param string $method
   *   The callable method to execute.
   * @param mixed[] $args
   *   The argument array to pass to our callback.
   *
   * @return static|bool
   *   The validator or FALSE if not found.
   *
   * @throws \BadMethodCallException
   *   If an attempt was made to call a validator modifier that doesn't exist.
   * @throws \Klein\Exceptions\ValidationException
   *   If the validation check returns FALSE.
   */
  public function __call(string $method, array $args): static|bool {
    $reverse = FALSE;
    $validator = $method;
    $method_substring = substr($method, 0, 2);

    if ($method_substring === 'is') {
      // Is<$validator>().
      $validator = substr($method, 2);
    }
    elseif ($method_substring === 'no') {
      // Not<$validator>().
      $validator = substr($method, 3);
      $reverse = TRUE;
    }

    $validator = strtolower($validator);

    if (!$validator || !isset(static::$methods[$validator]) || !is_callable(static::$methods[$validator])) {
      throw new \BadMethodCallException('Unknown method ' . $method . '()');
    }

    $validator = static::$methods[$validator];
    array_unshift($args, $this->string);

    switch (count($args)) {
      case 1:
        $result = $validator($args[0]);
        break;

      case 2:
        $result = $validator($args[0], $args[1]);
        break;

      case 3:
        $result = $validator($args[0], $args[1], $args[2]);
        break;

      case 4:
        $result = $validator($args[0], $args[1], $args[2], $args[3]);
        break;

      default:
        $result = call_user_func_array($validator, $args);
        break;
    }

    $result = (bool) ($result ^ $reverse);

    if (FALSE === $this->error) {
      return $result;
    }
    elseif (FALSE === $result) {
      throw new ValidationException($this->error ?: '');
    }

    return $this;
  }

}
