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

use Klein\Exceptions\ResponseAlreadySentException;

/**
 * Response.
 *
 * Base response class.
 */
class Response extends AbstractResponse {

  /**
   * Methods.
   */

  /**
   * Enable response chunking.
   *
   * @param ?string $string
   *   An optional string to send as a response "chunk".
   *
   * @return static
   *   This object.
   *
   * @link https://github.com/klein/klein.php/wiki/Response-Chunking
   * @link http://bit.ly/hg3gHb
   */
  public function chunk($string = NULL): static {
    parent::chunk();

    if (NULL !== $string) {
      printf("%x\r\n", strlen($string));
      echo "$string\r\n";
      flush();
    }

    return $this;
  }

  /**
   * Dump a variable.
   *
   * @param mixed $object
   *   The variable to dump.
   *
   * @return static
   *   This object.
   */
  public function dump(mixed $object): static {
    if (!is_scalar($object)) {
      $object = print_r($object, TRUE);
    }
    $this->append('<pre>' . htmlentities((string) $object, ENT_QUOTES) . "</pre><br />\n");

    return $this;
  }

  /**
   * Sends a file.
   *
   * It should be noted that this method disables caching
   * of the response by default, as dynamically created
   * files responses are usually downloads of some type
   * and rarely make sense to be HTTP cached.
   *
   * Also, this method removes any data/content that is
   * currently in the response body and replaces it with
   * the file's data
   *
   * @param string $path
   *   The path of the file to send.
   * @param ?string $filename
   *   The file's name.
   * @param ?string $mimetype
   *   The MIME type of the file.
   *
   * @return static
   *   This object.
   *
   * @throws \RuntimeException
   *   Thrown if the file could not be read.
   */
  public function file(string $path, ?string $filename = NULL, ?string $mimetype = NULL): static {
    if ($this->sent) {
      throw new ResponseAlreadySentException('Response has already been sent');
    }

    if (!is_file($path) || !is_readable($path)) {
      throw new \RuntimeException('The file could not be read');
    }

    $this->body('');
    $this->noCache();

    if (NULL === $filename) {
      $filename = basename($path);
    }
    if (NULL === $mimetype) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      if ($finfo !== FALSE) {
        $mimetype = finfo_file($finfo, $path) ?: 'unknown';
      }
    }

    $this->header('Content-type', $mimetype);
    $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

    // If the response is to be chunked, then the content length must not be
    // sent.
    // @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.4
    if (FALSE === $this->chunked) {
      $this->header('Content-length', filesize($path));
    }

    // Send our response data.
    $this->sendHeaders();

    $bytes_read = readfile($path);

    if (FALSE === $bytes_read) {
      throw new \RuntimeException('The file could not be read');
    }

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
   * Sends an object as json or jsonp by providing the padding prefix.
   *
   * It should be noted that this method disables caching
   * of the response by default, as json responses are usually
   * dynamic and rarely make sense to be HTTP cached.
   *
   * Also, this method removes any data/content that is
   * currently in the response body and replaces it with
   * the passed json encoded object.
   *
   * @param mixed $object
   *   The data to encode as JSON.
   * @param string $jsonp_prefix
   *   The name of the JSON-P function prefix.
   *
   * @return static
   *   This object.
   */
  public function json(mixed $object, ?string $jsonp_prefix = NULL): static {
    $this->body('');
    $this->noCache();

    $json = json_encode($object) ?: '';

    if (NULL !== $jsonp_prefix) {
      // Should ideally be application/json-p once adopted.
      $this->header('Content-Type', 'text/javascript');
      $this->body("$jsonp_prefix($json);");
    }
    else {
      $this->header('Content-Type', 'application/json');
      $this->body($json);
    }

    $this->send();

    return $this;
  }

}
