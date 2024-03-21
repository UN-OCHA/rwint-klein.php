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
 * Data Collection.
 *
 * A generic collection class to contain array-like data, specifically
 * designed to work with HTTP data (request params, session data, etc)
 *
 * Inspired by @fabpot's Symfony 2's HttpFoundation
 *
 * @link https://github.com/symfony/HttpFoundation/blob/master/ParameterBag.php
 *
 * @implements \IteratorAggregate<string|int, mixed>
 * @implements \ArrayAccess<string|int, mixed>
 */
class DataCollection implements DataCollectionInterface, \IteratorAggregate, \ArrayAccess, \Countable {

  /**
   * Class properties.
   */

  /**
   * Collection of data attributes.
   *
   * @var array<string|int, mixed>
   */
  protected array $attributes = [];


  /**
   * Methods.
   */

  /**
   * Constructor.
   *
   * @param array<string|int, mixed> $attributes
   *   The data attributes of this collection.
   */
  public function __construct(array $attributes = []) {
    $this->attributes = $attributes;
  }

  /**
   * Returns all of the key names in the collection.
   *
   * If an optional mask array is passed, this only
   * returns the keys that match the mask.
   *
   * @param ?array<string|int, mixed> $mask
   *   The parameter mask array.
   * @param bool $fill_with_nulls
   *   Whether or not to fill the returned array with values to match the given
   *   mask, even if they don't exist in the collection.
   *
   * @return array<string|int, mixed>
   *   All the key names in the collection.
   */
  public function keys(?array $mask = NULL, bool $fill_with_nulls = TRUE): array {
    if (NULL !== $mask) {
      /*
       * Make sure that the returned array has at least the values
       * passed into the mask, since the user will expect them to exist
       */
      if ($fill_with_nulls) {
        $keys = $mask;
      }
      else {
        $keys = [];
      }

      /*
       * Remove all of the values from the keys
       * that aren't in the passed mask
       */
      return array_intersect(
      array_keys($this->attributes),
      $mask
      ) + $keys;
    }

    return array_keys($this->attributes);
  }

  /**
   * Returns all of the attributes in the collection.
   *
   * If an optional mask array is passed, this only
   * returns the keys that match the mask.
   *
   * @param ?array<string|int> $mask
   *   The parameter mask array.
   * @param bool $fill_with_nulls
   *   Whether or not to fill the returned array with values to match the given
   *   mask, even if they don't exist in the collection.
   *
   * @return array<string|int, mixed>
   *   All the attributes in the collection.
   */
  public function all(?array $mask = NULL, bool $fill_with_nulls = TRUE): array {
    if (NULL !== $mask) {
      /*
       * Make sure that each key in the mask has at least a
       * null value, since the user will expect the key to exist
       */
      if ($fill_with_nulls) {
        $attributes = array_fill_keys($mask, NULL);
      }
      else {
        $attributes = [];
      }

      /*
       * Remove all of the keys from the attributes
       * that aren't in the passed mask
       */
      return array_intersect_key(
        $this->attributes,
        array_flip($mask)
      ) + $attributes;
    }

    return $this->attributes;
  }

  /**
   * Return an attribute of the collection.
   *
   * Return a default value if the key doesn't exist.
   *
   * @param string|int $key
   *   The name of the parameter to return.
   * @param mixed $default_val
   *   The default value of the parameter if it contains no value.
   *
   * @return mixed
   *   An atribute in the collection.
   */
  public function get(string|int $key, mixed $default_val = NULL): mixed {
    if (isset($this->attributes[$key])) {
      return $this->attributes[$key];
    }

    return $default_val;
  }

  /**
   * Set an attribute of the collection.
   *
   * @param string|int $key
   *   The name of the parameter to set.
   * @param mixed $value
   *   The value of the parameter to set.
   *
   * @return static
   *   This object.
   *
   * @throws \RuntimeException
   *   Exception if the value to set is not of the expected type.
   */
  public function set(string|int $key, mixed $value): static {
    $this->attributes[$key] = $value;

    return $this;
  }

  /**
   * Replace the collection's attributes.
   *
   * @param array<string|int, mixed> $attributes
   *   The attributes to replace the collection's with.
   *
   * @return static
   *   This object.
   */
  public function replace(array $attributes = []): static {
    $this->attributes = $attributes;

    return $this;
  }

  /**
   * Merge attributes with the collection's attributes.
   *
   * Optionally allows a second bool parameter to merge the attributes
   * into the collection in a "hard" manner, using the "array_replace"
   * method instead of the usual "array_merge" method.
   *
   * @param array<string|int, mixed> $attributes
   *   The attributes to merge into the collection.
   * @param bool $hard
   *   Whether or not to make the merge "hard".
   *
   * @return static
   *   This Object.
   */
  public function merge(array $attributes = [], bool $hard = FALSE): static {
    // Don't waste our time with an "array_merge" call if the array is empty.
    if (!empty($attributes)) {
      // Hard merge?
      if ($hard) {
        $this->attributes = array_replace(
        $this->attributes,
        $attributes
        );
      }
      else {
        $this->attributes = array_merge(
        $this->attributes,
        $attributes
        );
      }
    }

    return $this;
  }

  /**
   * See if an attribute exists in the collection.
   *
   * @param string|int $key
   *   The name of the parameter.
   *
   * @return bool
   *   TRUE if the attribute exists.
   */
  public function exists(string|int $key): bool {
    // Don't use "isset", since it returns FALSE for null values.
    return array_key_exists($key, $this->attributes);
  }

  /**
   * Remove an attribute from the collection.
   *
   * @param string|int $key
   *   The name of the parameter.
   */
  public function remove(string|int $key): void {
    unset($this->attributes[$key]);
  }

  /**
   * Clear the collection's contents.
   *
   * Semantic alias of a no-argument `$this->replace` call.
   *
   * @return static
   *   This object.
   */
  public function clear(): static {
    return $this->replace();
  }

  /**
   * Check if the collection is empty.
   *
   * @return bool
   *   TRUE if the collection is empty.
   */
  public function isEmpty(): bool {
    return empty($this->attributes);
  }

  /**
   * A quick convenience method to get an empty clone of the collection.
   *
   * Great for dependency injection. :).
   *
   * @return \Klein\DataCollection\DataCollectionInterface
   *   A clone of the collection.
   */
  public function cloneEmpty(): DataCollectionInterface {
    $clone = clone $this;
    $clone->clear();

    return $clone;
  }

  /*
   * Magic method implementations
   */

  /**
   * Magic "__get" method.
   *
   * Allows the ability to arbitrarily request an attribute from
   * this instance while treating it as an instance property.
   *
   * @param string|int $key
   *   The name of the parameter to return.
   *
   * @return mixed
   *   An attribute.
   *
   * @see ::get()
   */
  public function __get(string|int $key): mixed {
    return $this->get($key);
  }

  /**
   * Magic "__set" method.
   *
   * Allows the ability to arbitrarily set an attribute from
   * this instance while treating it as an instance property.
   *
   * @param string|int $key
   *   The name of the parameter to set.
   * @param mixed $value
   *   The value of the parameter to set.
   *
   * @see ::set()
   */
  public function __set(string|int $key, mixed $value): void {
    $this->set($key, $value);
  }

  /**
   * Magic "__isset" method.
   *
   * Allows the ability to arbitrarily check the existence of an attribute
   * from this instance while treating it as an instance property.
   *
   * @param string|int $key
   *   The name of the parameter.
   *
   * @return bool
   *   TRUE if the attribute exists.
   *
   * @see ::exists()
   */
  public function __isset(string|int $key): bool {
    return $this->exists($key);
  }

  /**
   * Magic "__unset" method.
   *
   * Allows the ability to arbitrarily remove an attribute from
   * this instance while treating it as an instance property.
   *
   * @param string|int $key
   *   The name of the parameter.
   *
   * @see ::remove()
   */
  public function __unset(string|int $key): void {
    $this->remove($key);
  }

  /*
   * Interface required method implementations.
   */

  /**
   * Get the aggregate iterator.
   *
   * IteratorAggregate interface required method.
   *
   * @return \Traversable<string|int, mixed>
   *   Iterator.
   *
   * @see \IteratorAggregate::getIterator()
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->attributes);
  }

  /**
   * Get an attribute via array syntax.
   *
   * Allows the access of attributes of this instance while treating it like
   * an array.
   *
   * @param mixed $key
   *   The name of the parameter to return.
   *
   * @return mixed
   *   An attribute.
   *
   * @see \ArrayAccess::offsetGet()
   * @see ::get()
   */
  public function offsetGet(mixed $key): mixed {
    return is_string($key) || is_int($key) ? $this->get($key) : NULL;
  }

  /**
   * Set an attribute via array syntax.
   *
   * Allows the access of attributes of this instance while treating it like
   * an array.
   *
   * @param mixed $key
   *   The name of the parameter to set.
   * @param mixed $value
   *   The value of the parameter to set.
   *
   * @see \ArrayAccess::offsetSet()
   * @see ::set()
   */
  public function offsetSet(mixed $key, mixed $value): void {
    if (is_string($key) || is_int($key)) {
      $this->set($key, $value);
    }
  }

  /**
   * Check existence an attribute via array syntax.
   *
   * Allows the access of attributes of this instance while treating it like
   * an array.
   *
   * @param mixed $key
   *   The name of the parameter.
   *
   * @return bool
   *   TRUE of the attribute exists.
   *
   * @see \ArrayAccess::offsetExists()
   * @see ::exists()
   */
  public function offsetExists(mixed $key): bool {
    return (is_string($key) || is_int($key)) && $this->exists($key);
  }

  /**
   * Remove an attribute via array syntax.
   *
   * Allows the access of attributes of this instance while treating it like
   * an array.
   *
   * @param mixed $key
   *   The name of the parameter.
   *
   * @see \ArrayAccess::offsetUnset()
   * @see ::remove()
   */
  public function offsetUnset(mixed $key): void {
    if (is_string($key) || is_int($key)) {
      $this->remove($key);
    }
  }

  /**
   * Count the attributes via a simple "count" call.
   *
   * Allows the use of the "count" function (or any internal counters)
   * to simply count the number of attributes in the collection.
   *
   * @return int
   *   The number of attributes in the collection.
   *
   * @see \Countable::count()
   */
  public function count(): int {
    return count($this->attributes);
  }

}
