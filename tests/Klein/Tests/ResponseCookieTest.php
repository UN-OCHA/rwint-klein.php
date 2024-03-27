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

namespace Klein\Tests;

use Klein\ResponseCookie;

/**
 * Response Cookie Test.
 */
class ResponseCookieTest extends AbstractKleinTest {

  /**
   * Data Providers and Methods.
   */

  /**
   * Sample data provider.
   *
   * @return array
   *   Sample data.
   */
  public function sampleDataProvider() {
    // Populate our sample data.
    $default_sample_data = [
      'name' => '',
      'value' => '',
      'expire' => 0,
      'path' => '',
      'domain' => '',
      'secure' => FALSE,
      'http_only' => FALSE,
    ];

    $sample_data = [
      'name' => 'Trevor',
      'value' => 'is a programmer',
      'expire' => 3600,
      'path' => '/',
      'domain' => 'example.com',
      'secure' => FALSE,
      'http_only' => FALSE,
    ];

    $sample_data_other = [
      'name' => 'Chris',
      'value' => 'is a boss',
      'expire' => 60,
      'path' => '/app/',
      'domain' => 'github.com',
      'secure' => TRUE,
      'http_only' => TRUE,
    ];

    return [
      [$default_sample_data, $sample_data, $sample_data_other],
    ];
  }

  /**
   * Tests.
   */

  /**
   * Test name get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testNameGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie($sample_data['name']);

    $this->assertSame($sample_data['name'], $response_cookie->getName());
    $this->assertIsString($response_cookie->getName());

    $response_cookie->setName($sample_data_other['name']);

    $this->assertSame($sample_data_other['name'], $response_cookie->getName());
    $this->assertIsString($response_cookie->getName());
  }

  /**
   * Test value get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testValueGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie($defaults['name'], $sample_data['value']);

    $this->assertSame($sample_data['value'], $response_cookie->getValue());
    $this->assertIsString($response_cookie->getValue());

    $response_cookie->setValue($sample_data_other['value']);

    $this->assertSame($sample_data_other['value'], $response_cookie->getValue());
    $this->assertIsString($response_cookie->getValue());
  }

  /**
   * Test expire get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testExpireGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie(
      $defaults['name'],
      '',
      $sample_data['expire']
    );

    $this->assertSame($sample_data['expire'], $response_cookie->getExpire());
    $this->assertIsInt($response_cookie->getExpire());

    $response_cookie->setExpire($sample_data_other['expire']);

    $this->assertSame($sample_data_other['expire'], $response_cookie->getExpire());
    $this->assertIsInt($response_cookie->getExpire());
  }

  /**
   * Test path get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testPathGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie(
      $defaults['name'],
      '',
      0,
      $sample_data['path']
    );

    $this->assertSame($sample_data['path'], $response_cookie->getPath());
    $this->assertIsString($response_cookie->getPath());

    $response_cookie->setPath($sample_data_other['path']);

    $this->assertSame($sample_data_other['path'], $response_cookie->getPath());
    $this->assertIsString($response_cookie->getPath());
  }

  /**
   * Test domain get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testDomainGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie(
      $defaults['name'],
      '',
      0,
      '',
      $sample_data['domain']
    );

    $this->assertSame($sample_data['domain'], $response_cookie->getDomain());
    $this->assertIsString($response_cookie->getDomain());

    $response_cookie->setDomain($sample_data_other['domain']);

    $this->assertSame($sample_data_other['domain'], $response_cookie->getDomain());
    $this->assertIsString($response_cookie->getDomain());
  }

  /**
   * Test secure get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testSecureGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie(
      $defaults['name'],
      '',
      0,
      '',
      '',
      $sample_data['secure']
    );

    $this->assertSame($sample_data['secure'], $response_cookie->getSecure());
    $this->assertIsBool($response_cookie->getSecure());

    $response_cookie->setSecure($sample_data_other['secure']);

    $this->assertSame($sample_data_other['secure'], $response_cookie->getSecure());
    $this->assertIsBool($response_cookie->getSecure());
  }

  /**
   * Test http only get set.
   *
   * @dataProvider sampleDataProvider
   */
  public function testHttpOnlyGetSet($defaults, $sample_data, $sample_data_other) {
    $response_cookie = new ResponseCookie(
      $defaults['name'],
      '',
      0,
      '',
      '',
      FALSE,
      $sample_data['http_only']
    );

    $this->assertSame($sample_data['http_only'], $response_cookie->getHttpOnly());
    $this->assertIsBool($response_cookie->getHttpOnly());

    $response_cookie->setHttpOnly($sample_data_other['http_only']);

    $this->assertSame($sample_data_other['http_only'], $response_cookie->getHttpOnly());
    $this->assertIsBool($response_cookie->getHttpOnly());
  }

}
