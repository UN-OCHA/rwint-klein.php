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

namespace Klein\Tests\DataCollection;

use Klein\DataCollection\ResponseCookieDataCollection;
use Klein\ResponseCookie;
use Klein\Tests\AbstractKleinTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Response Cookie Data Collection Test.
 */
class ResponseCookieDataCollectionTest extends AbstractKleinTest {

  /*
   * Data Providers and Methods
   */

  /**
   * Sample data provider.
   *
   * @return array
   *   Sample data.
   */
  public static function sampleDataProvider(): array {
    $sample_cookie = new ResponseCookie(
      'Trevor',
      'is a programmer',
      3600,
      '/',
      'example.com',
      FALSE,
      FALSE
    );

    $sample_other_cookie = new ResponseCookie(
      'Chris',
      'is a boss',
      60,
      '/app/',
      'github.com',
      TRUE,
      TRUE
    );

    return [
      [$sample_cookie, $sample_other_cookie],
    ];
  }

  /**
   * Tests.
   */

  /**
   * Test set.
   */
  #[DataProvider('sampleDataProvider')]
  public function testSet($sample_cookie, $sample_other_cookie) {
    // Create our collection with NO data.
    $data_collection = new ResponseCookieDataCollection();

    // Set our data from our test data.
    $data_collection->set('first', $sample_cookie);

    $this->assertSame($sample_cookie, $data_collection->get('first'));
    $this->assertTrue($data_collection->get('first') instanceof ResponseCookie);
  }

  /**
   * Test set string converts to cookie.
   */
  public function testSetStringConvertsToCookie() {
    // Create our collection with NO data.
    $data_collection = new ResponseCookieDataCollection();

    // Set our data from our test data.
    $data_collection->set('first', 'value');

    $this->assertNotSame('value', $data_collection->get('first'));
    $this->assertTrue($data_collection->get('first') instanceof ResponseCookie);
  }

  /**
   * Test constructor routes through set.
   */
  #[DataProvider('sampleDataProvider')]
  public function testConstructorRoutesThroughSet($sample_cookie, $sample_other_cookie) {
    $array_of_cookie_instances = [
      $sample_cookie,
      $sample_other_cookie,
      new ResponseCookie('test'),
    ];

    // Create our collection with NO data.
    $data_collection = new ResponseCookieDataCollection($array_of_cookie_instances);
    $this->assertSame($array_of_cookie_instances, $data_collection->all());

    foreach ($data_collection as $cookie) {
      $this->assertTrue($cookie instanceof ResponseCookie);
    }
  }

}
