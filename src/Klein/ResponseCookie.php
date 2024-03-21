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
 * ResponseCookie.
 *
 * Class to represent an HTTP response cookie.
 */
class ResponseCookie {

  /**
   * Class properties.
   */

  /**
   * The name of the cookie.
   *
   * @var string
   */
  protected string $name;

  /**
   * The string "value" of the cookie.
   *
   * @var string
   */
  protected string $value;

  /**
   * The date/time that the cookie should expire.
   *
   * Represented by a Unix "Timestamp".
   *
   * @var int
   */
  protected int $expire;

  /**
   * The path on the server that the cookie will be available on.
   *
   * @var string
   */
  protected string $path;

  /**
   * The domain that the cookie is available to.
   *
   * @var string
   */
  protected string $domain;

  /**
   * Whether to only transfer the cookie should over an HTTPS connection or not.
   *
   * @var bool
   */
  protected bool $secure;

  /**
   * Transfer the cookie over HTTP only.
   *
   * Whether the cookie will be available through HTTP only (not available to
   * be accessed through client-side scripting languages like JavaScript).
   *
   * @var bool
   */
  protected bool $httpOnly;

  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param string $name
   *   The name of the cookie.
   * @param string $value
   *   The value to set the cookie with.
   * @param int $expire
   *   The time that the cookie should expire.
   * @param string $path
   *   The path of which to restrict the cookie.
   * @param string $domain
   *   The domain of which to restrict the cookie.
   * @param bool $secure
   *   Flag of whether the cookie should only be sent over a HTTPS connection.
   * @param bool $http_only
   *   Flag of whether the cookie should only be accessible over the HTTP
   *   protocol.
   */
  public function __construct(
    string $name,
    string $value = '',
    int $expire = 0,
    string $path = '',
    string $domain = '',
    bool $secure = FALSE,
    bool $http_only = FALSE
  ) {
    // Initialize our properties.
    $this->setName($name);
    $this->setValue($value);
    $this->setExpire($expire);
    $this->setPath($path);
    $this->setDomain($domain);
    $this->setSecure($secure);
    $this->setHttpOnly($http_only);
  }

  /**
   * Gets the cookie's name.
   *
   * @return string
   *   Cookie name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Sets the cookie's name.
   *
   * @param string $name
   *   Cookie name.
   *
   * @return static
   *   This object.
   */
  public function setName(string $name): static {
    $this->name = $name;

    return $this;
  }

  /**
   * Gets the cookie's value.
   *
   * @return string
   *   Cookie value.
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * Sets the cookie's value.
   *
   * @param string $value
   *   Value.
   *
   * @return static
   *   This object.
   */
  public function setValue(string $value): static {
    $this->value = $value;

    return $this;
  }

  /**
   * Gets the cookie's expire time.
   *
   * @return int
   *   Expire time.
   */
  public function getExpire(): int {
    return $this->expire;
  }

  /**
   * Sets the cookie's expire time.
   *
   * The time should be an integer representing a Unix timestamp.
   *
   * @param int $expire
   *   Expire time.
   *
   * @return static
   *   This object.
   */
  public function setExpire(int $expire): static {
    $this->expire = $expire;

    return $this;
  }

  /**
   * Gets the cookie's path.
   *
   * @return string
   *   Cookie path.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Sets the cookie's path.
   *
   * @param string $path
   *   Cookie path.
   *
   * @return static
   *   This object.
   */
  public function setPath(string $path): static {
    $this->path = $path;

    return $this;
  }

  /**
   * Gets the cookie's domain.
   *
   * @return string
   *   Cookie domain.
   */
  public function getDomain(): string {
    return $this->domain;
  }

  /**
   * Sets the cookie's domain.
   *
   * @param string $domain
   *   Cookie domain.
   *
   * @return static
   *   This object.
   */
  public function setDomain(string $domain): static {
    $this->domain = $domain;

    return $this;
  }

  /**
   * Gets the cookie's secure only flag.
   *
   * @return bool
   *   TRUE if secure.
   */
  public function getSecure(): bool {
    return $this->secure;
  }

  /**
   * Sets the cookie's secure only flag.
   *
   * @param bool $secure
   *   TRUE to secure.
   *
   * @return static
   *   This object.
   */
  public function setSecure(bool $secure): static {
    $this->secure = $secure;

    return $this;
  }

  /**
   * Gets the cookie's HTTP only flag.
   *
   * @return bool
   *   TRUE if http only.
   */
  public function getHttpOnly(): bool {
    return $this->httpOnly;
  }

  /**
   * Sets the cookie's HTTP only flag.
   *
   * @param bool $http_only
   *   TRUE to set HTTP only.
   *
   * @return static
   *   This object.
   */
  public function setHttpOnly(bool $http_only): static {
    $this->httpOnly = $http_only;

    return $this;
  }

}
