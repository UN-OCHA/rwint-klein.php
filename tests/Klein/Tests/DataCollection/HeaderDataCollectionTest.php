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

use Klein\DataCollection\HeaderDataCollection;
use Klein\Tests\AbstractKleinTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Header Data Collection Test.
 */
class HeaderDataCollectionTest extends AbstractKleinTest {

  /**
   * Non existent key in the sample data.
   *
   * @var string
   */
  protected static string $nonexistentKey = 'non-standard-header';

  /**
   * Data Providers and Methods.
   */

  /**
   * Prepare sample data.
   *
   * Quickly makes sure that no sample data arrays
   * have any keys that match the "nonexistentKey"
   *
   * @param array $sample_data
   *   Sample data.
   */
  protected static function prepareSampleData(array &$sample_data): void {
    if (isset($sample_data[static::$nonexistentKey])) {
      unset($sample_data[static::$nonexistentKey]);
    }

    foreach ($sample_data as &$data) {
      if (is_array($data)) {
        self::prepareSampleData($data);
      }
    }
    reset($sample_data);
  }

  /**
   * Sample data provider.
   *
   * @return array
   *   Sample data.
   */
  public static function sampleDataProvider(): array {
    // Populate our sample data.
    $sample_data = [
      'HOST' => 'localhost:8000',
      'CONNECTION' => 'keep-alive',
      'CONTENT_LENGTH' => '137',
      'USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31'
      . ' (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31',
      'CACHE_CONTROL' => 'no-cache',
      'ORIGIN' => 'chrome-extension://fdmmgilgnpjigdojojpjoooidkmcomcm',
      'AUTHORIZATION' => 'Basic MTIzOjQ1Ng==',
      'CONTENT_TYPE' => 'multipart/form-data; boundary=----WebKitFormBoundaryDhtDHBYppyHdrZe7',
      'ACCEPT' => '*/*',
      'ACCEPT_ENCODING' => 'gzip,deflate,sdch',
      'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
      'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
    ];

    self::prepareSampleData($sample_data);

    $data_collection = new HeaderDataCollection($sample_data);

    return [
      [$sample_data, $data_collection],
    ];
  }

  /**
   * Tests.
   */

  /**
   * Test constructor correctly formatted.
   */
  #[DataProvider('sampleDataProvider')]
  public function testConstructorCorrectlyFormatted($sample_data, $data_collection) {
    $this->assertNotSame($sample_data, $data_collection->all());
    $this->assertArrayNotHasKey('HOST', $data_collection->all());
    $this->assertContains('localhost:8000', $data_collection->all());
  }

  /**
   * Test get set normalization.
   */
  public function testGetSetNormalization() {
    $data_collection = new HeaderDataCollection();

    $this->assertIsInt($data_collection->getNormalization());

    $data_collection->setNormalization(
      HeaderDataCollection::NORMALIZE_TRIM & HeaderDataCollection::NORMALIZE_CASE
    );

    $this->assertSame(
      HeaderDataCollection::NORMALIZE_TRIM & HeaderDataCollection::NORMALIZE_CASE,
      $data_collection->getNormalization()
    );
  }

  /**
   * Test get.
   */
  #[DataProvider('sampleDataProvider')]
  public function testGet($sample_data, $data_collection) {
    $default = 'WOOT!';

    $this->assertSame($sample_data['USER_AGENT'], $data_collection->get('user-agent'));
    $this->assertSame($default, $data_collection->get(static::$nonexistentKey, $default));
    $this->assertNull($data_collection->get(static::$nonexistentKey));
  }

  /**
   * Test set.
   */
  public function testSet() {
    // Test data.
    $data = [
      'DOG_NAME' => 'cooper',
    ];

    // Create our collection with NO data.
    $data_collection = new HeaderDataCollection();

    // Set our data from our test data.
    $data_collection->set(key($data), current($data));

    // Make sure the set worked, but the key is different.
    $this->assertSame(current($data), $data_collection->get(key($data)));
    $this->assertArrayNotHasKey(key($data), $data_collection->all());
  }

  /**
   * Test exists.
   */
  #[DataProvider('sampleDataProvider')]
  public function testExists($sample_data, $data_collection) {
    // Make sure the set worked, but the key is different.
    $this->assertTrue($data_collection->exists('HOST'));
    $this->assertFalse($data_collection->exists(static::$nonexistentKey));
    $this->assertArrayNotHasKey('HOST', $data_collection->all());
  }

  /**
   * Test remove.
   */
  #[DataProvider('sampleDataProvider')]
  public function testRemove($sample_data, $data_collection) {
    $this->assertTrue($data_collection->exists('HOST'));
    $this->assertArrayNotHasKey('HOST', $data_collection->all());

    $data_collection->remove('HOST');

    $this->assertFalse($data_collection->exists('HOST'));
  }

  /**
   * Test normalize key delimiters.
   */
  public function testNormalizeKeyDelimiters() {
    // Test data.
    $header = 'Access_Control Allow-Origin';

    $canonicalized_key = HeaderDataCollection::normalizeKeyDelimiters($header);

    $this->assertNotSame($header, $canonicalized_key);

    $this->assertSame('Access-Control-Allow-Origin', $canonicalized_key);
  }

  /**
   * Test canonicalize key.
   */
  public function testCanonicalizeKey() {
    // Test data.
    $header = 'content-TYPE';

    $canonicalized_key = HeaderDataCollection::canonicalizeKey($header);

    $this->assertNotSame($header, $canonicalized_key);

    $this->assertSame('Content-Type', $canonicalized_key);
  }

}
