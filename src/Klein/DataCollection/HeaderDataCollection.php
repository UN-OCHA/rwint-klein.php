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

namespace Klein\DataCollection;

/**
 * Header Data Collection.
 *
 * A DataCollection for HTTP headers.
 */
class HeaderDataCollection extends DataCollection {

  /**
   * Constants.
   */

  /**
   * Normalization option.
   *
   * Don't normalize.
   *
   * @type int
   */
  const NORMALIZE_NONE = 0;

  /**
   * Normalization option.
   *
   * Normalize the outer whitespace of the header.
   *
   * @type int
   */
  const NORMALIZE_TRIM = 1;

  /**
   * Normalization option.
   *
   * Normalize the delimiters of the header.
   *
   * @type int
   */
  const NORMALIZE_DELIMITERS = 2;

  /**
   * Normalization option.
   *
   * Normalize the case of the header.
   *
   * @type int
   */
  const NORMALIZE_CASE = 4;

  /**
   * Normalization option.
   *
   * Normalize the header into canonical format.
   *
   * @type int
   */
  const NORMALIZE_CANONICAL = 8;

  /**
   * Normalization option.
   *
   * Normalize using all normalization techniques.
   *
   * @type int
   */
  const NORMALIZE_ALL = -1;


  /**
   * Properties.
   */

  /**
   * Header key normalization technique/style.
   *
   * The header key normalization technique/style to use when accessing headers
   * in the collection.
   *
   * @var int
   */
  protected int $normalization = self::NORMALIZE_ALL;


  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param mixed[] $headers
   *   The headers of this collection.
   * @param int $normalization
   *   $normalization The header key normalization technique/style to use.
   *
   * @override (doesn't call our parent).
   */
  public function __construct(array $headers = [], int $normalization = self::NORMALIZE_ALL) {
    $this->normalization = (int) $normalization;

    foreach ($headers as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * Get the header key normalization technique/style to use.
   *
   * @return int
   *   The normalization technique/style.
   */
  public function getNormalization(): int {
    return $this->normalization;
  }

  /**
   * Set the header key normalization technique/style to use.
   *
   * @param int $normalization
   *   Normalization technique/style.
   *
   * @return static
   *   This object.
   */
  public function setNormalization($normalization): static {
    $this->normalization = (int) $normalization;

    return $this;
  }

  /**
   * Get a header.
   *
   * {@inheritdoc}
   */
  public function get(string|int $key, mixed $default_val = NULL): mixed {
    $key = $this->normalizeKey($key);

    return parent::get($key, $default_val);
  }

  /**
   * Set a header.
   *
   * {@inheritdoc}
   */
  public function set(string|int $key, mixed $value): static {
    $key = $this->normalizeKey($key);

    return parent::set($key, $value);
  }

  /**
   * Check if a header exists.
   *
   * {@inheritdoc}
   */
  public function exists(string|int $key): bool {
    $key = $this->normalizeKey($key);

    return parent::exists($key);
  }

  /**
   * Remove a header.
   *
   * {@inheritdoc}
   */
  public function remove(string|int $key): void {
    $key = $this->normalizeKey($key);

    parent::remove($key);
  }

  /**
   * Normalize a header key based on our set normalization style.
   *
   * @param string|int $key
   *   The ("field") key of the header.
   *
   * @return string
   *   The normalized header.
   */
  protected function normalizeKey(string|int $key): string {
    $key = (string) $key;

    if ($this->normalization & static::NORMALIZE_TRIM) {
      $key = trim($key);
    }

    if ($this->normalization & static::NORMALIZE_DELIMITERS) {
      $key = static::normalizeKeyDelimiters($key);
    }

    if ($this->normalization & static::NORMALIZE_CASE) {
      $key = strtolower($key);
    }

    if ($this->normalization & static::NORMALIZE_CANONICAL) {
      $key = static::canonicalizeKey($key);
    }

    return $key;
  }

  /**
   * Normalize a header key's delimiters.
   *
   * This will convert any space or underscore characters
   * to a more standard hyphen (-) character.
   *
   * @param string $key
   *   The ("field") key of the header.
   *
   * @return string
   *   Normalized header key's delimiters.
   */
  public static function normalizeKeyDelimiters(string $key): string {
    return str_replace([' ', '_'], '-', $key);
  }

  /**
   * Canonicalize a header key.
   *
   * The canonical format is all lower case except for
   * the first letter of "words" separated by a hyphen.
   *
   * @param string $key
   *   The ("field") key of the header.
   *
   * @return string
   *   Canonicalized header key.
   *
   * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
   */
  public static function canonicalizeKey(string $key): string {
    $words = explode('-', strtolower($key));

    foreach ($words as &$word) {
      $word = ucfirst($word);
    }

    return implode('-', $words);
  }

}
