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

use Klein\DataCollection\DataCollection;
use Klein\Tests\AbstractKleinTest;

/**
 * Data Collection Test.
 */
class DataCollectionTest extends AbstractKleinTest {

  /**
   * Non existent key in the sample data.
   *
   * @var string
   */
  protected static string $nonexistentKey = 'key-name-doesnt-exist';

  /**
   * Data Providers and Methods.
   */

  /**
   * Prepare sample data.
   *
   * Quickly makes sure that no sample data arrays
   * have any keys that match the "nonexistentKey".
   *
   * @param mixed[] $sample_data
   *   Sample data.
   */
  protected function prepareSampleData(array &$sample_data) {
    if (isset($sample_data[static::$nonexistentKey])) {
      unset($sample_data[static::$nonexistentKey]);
    }

    foreach ($sample_data as &$data) {
      if (is_array($data)) {
        $this->prepareSampleData($data);
      }
    }
    reset($sample_data);
  }

  /**
   * Sample data provider.
   *
   * @return array
   *   Sample data provider.
   */
  public function sampleDataProvider() {
    // Populate our sample data.
    $sample_data = [
      'id' => 1337,
      'name' => [
        'first' => 'Trevor',
        'last' => 'Suarez',
      ],
      'float' => 13.37,
      'thing' => new \stdClass(),
    ];

    $this->prepareSampleData($sample_data);

    $data_collection = new DataCollection($sample_data);

    return [
      [$sample_data, $data_collection],
    ];
  }

  /**
   * Totally different sample data provider.
   *
   * @return array
   *   Different sample data provider.
   */
  public function totallyDifferentSampleDataProvider() {
    // Populate our sample data.
    $totally_different_sample_data = [
      '_why' => 'the lucky stiff',
      'php' => 'has become beautiful',
      'yay' => 'life is very good. :)',
    ];

    $this->prepareSampleData($totally_different_sample_data);

    return [
      [$totally_different_sample_data],
    ];
  }

  /**
   * Tests.
   */

  /**
   * Test keys.
   *
   * @dataProvider sampleDataProvider
   */
  public function testKeys($sample_data, $data_collection) {
    // Test basic data similarity.
    $this->assertSame(array_keys($sample_data), $data_collection->keys());

    // Create mask.
    $mask = ['float', static::$nonexistentKey];

    $this->assertContains($mask[0], $data_collection->keys($mask));
    $this->assertContains($mask[1], $data_collection->keys($mask));
    $this->assertNotContains(key($sample_data), $data_collection->keys($mask));

    // Test not filling will nulls.
    $this->assertContains($mask[0], $data_collection->keys($mask, FALSE));
    $this->assertNotContains($mask[1], $data_collection->keys($mask, FALSE));
  }

  /**
   * Test all.
   *
   * @dataProvider sampleDataProvider
   */
  public function testAll($sample_data, $data_collection) {
    // Test basic data similarity.
    $this->assertSame($sample_data, $data_collection->all());

    // Create mask.
    $mask = ['float', static::$nonexistentKey];

    $this->assertArrayHasKey($mask[0], $data_collection->all($mask));
    $this->assertArrayHasKey($mask[1], $data_collection->all($mask));
    $this->assertArrayNotHasKey('id', $data_collection->all($mask));
    $this->assertArrayNotHasKey('name', $data_collection->all($mask));

    // Test not filling will nulls.
    $this->assertArrayHasKey($mask[0], $data_collection->all($mask, FALSE));
    $this->assertArrayNotHasKey($mask[1], $data_collection->all($mask, FALSE));
  }

  /**
   * Test get.
   *
   * @dataProvider sampleDataProvider
   */
  public function testGet($sample_data, $data_collection) {
    $default = 'WOOT!';

    $this->assertSame($sample_data['id'], $data_collection->get('id'));
    $this->assertSame($default, $data_collection->get(static::$nonexistentKey, $default));
    $this->assertNull($data_collection->get(static::$nonexistentKey));
  }

  /**
   * Test set.
   */
  public function testSet() {
    // Test data.
    $data = [
      'dog' => 'cooper',
    ];

    // Create our collection with NO data.
    $data_collection = new DataCollection();

    // Make sure its first empty.
    $this->assertSame([], $data_collection->all());

    // Set our data from our test data.
    $return_val = $data_collection->set(key($data), current($data));

    // Make sure the set worked.
    $this->assertSame(current($data), $data_collection->get(key($data)));

    // Make sure it returned the instance during "set".
    $this->assertEquals($return_val, $data_collection);
    $this->assertSame($return_val, $data_collection);
  }

  /**
   * Test replace.
   *
   * @dataProvider sampleDataProvider
   */
  public function testReplace($sample_data, $data_collection) {
    $totally_different_sample_data = current(
      current($this->totallyDifferentSampleDataProvider())
    );

    $data_collection->replace($totally_different_sample_data);

    $this->assertNotSame($sample_data, $totally_different_sample_data);
    $this->assertNotSame($sample_data, $data_collection->all());
    $this->assertSame($totally_different_sample_data, $data_collection->all());
  }

  /**
   * Test merge.
   *
   * @dataProvider sampleDataProvider
   */
  public function testMerge($sample_data, $data_collection) {
    $totally_different_sample_data = current(
      current($this->totallyDifferentSampleDataProvider())
    );

    $merged_data = array_merge($sample_data, $totally_different_sample_data);

    $data_collection->merge($totally_different_sample_data);

    $this->assertNotSame($sample_data, $totally_different_sample_data);
    $this->assertNotSame($sample_data, $data_collection->all());
    $this->assertNotSame($totally_different_sample_data, $data_collection->all());
    $this->assertSame($merged_data, $data_collection->all());
  }

  /**
   * Test merged hard.
   *
   * @dataProvider sampleDataProvider
   */
  public function testMergeHard($sample_data, $data_collection) {
    $totally_different_sample_data = current(
      current($this->totallyDifferentSampleDataProvider())
    );

    $replaced_data = array_replace($sample_data, $totally_different_sample_data);

    $data_collection->merge($totally_different_sample_data, TRUE);

    $this->assertNotSame($sample_data, $totally_different_sample_data);
    $this->assertNotSame($sample_data, $data_collection->all());
    $this->assertNotSame($totally_different_sample_data, $data_collection->all());
    $this->assertSame($replaced_data, $data_collection->all());
  }

  /**
   * Test exists.
   *
   * @dataProvider sampleDataProvider
   */
  public function testExists($sample_data, $data_collection) {
    $this->assertTrue($data_collection->exists('id'));
    $this->assertFalse($data_collection->exists(static::$nonexistentKey));
  }

  /**
   * Test remove.
   *
   * @dataProvider sampleDataProvider
   */
  public function testRemove($sample_data, $data_collection) {
    $this->assertTrue($data_collection->exists('id'));

    $data_collection->remove('id');

    $this->assertFalse($data_collection->exists('id'));
  }

  /**
   * Test clear.
   *
   * @dataProvider sampleDataProvider
   */
  public function testClear($sample_data, $data_collection) {
    $original_data = $data_collection->all();

    $data_collection->clear();

    $this->assertNotSame($original_data, $data_collection->all());
    $this->assertSame([], $data_collection->all());
  }

  /**
   * Test magic get.
   *
   * @dataProvider sampleDataProvider
   */
  public function testMagicGet($sample_data, $data_collection) {
    $this->assertSame($sample_data['float'], $data_collection->float);
    $this->assertNull($data_collection->{static::$nonexistentKey});
  }

  /**
   * Test magic set.
   */
  public function testMagicSet() {
    // Test data.
    $data = [
      'dog' => 'cooper',
    ];

    // Create our collection with NO data.
    $data_collection = new DataCollection();

    // Set our data from our test data.
    $data_collection->{key($data)} = current($data);

    // Make sure the set worked.
    $this->assertSame(current($data), $data_collection->get(key($data)));
  }

  /**
   * Test magic isset.
   *
   * @dataProvider sampleDataProvider
   */
  public function testMagicIsset($sample_data, $data_collection) {
    $this->assertTrue(isset($data_collection->id));
    $this->assertTrue(isset($data_collection->name));
    $this->assertTrue(isset($data_collection->float));
    $this->assertFalse(isset($data_collection->{static::$nonexistentKey}));
  }

  /**
   * Test magic unset.
   *
   * @dataProvider sampleDataProvider
   */
  public function testMagicUnset($sample_data, $data_collection) {
    $this->assertTrue(isset($data_collection->id));

    unset($data_collection->id);

    $this->assertFalse(isset($data_collection->id));
  }

  /**
   * Test IteratorAggregate.
   *
   * @dataProvider sampleDataProvider
   */
  public function testIteratorAggregate($sample_data, $data_collection) {
    $filled_data = [];

    foreach ($data_collection as $key => $data) {
      $filled_data[$key] = $data;
    }

    $this->assertSame($filled_data, $sample_data);
  }

  /**
   * Test ArrayAccess get.
   *
   * @dataProvider sampleDataProvider
   */
  public function testArrayAccessGet($sample_data, $data_collection) {
    $this->assertSame($sample_data['float'], $data_collection['float']);
    $this->assertNull($data_collection[static::$nonexistentKey]);
  }

  /**
   * Test ArrayAccess set.
   */
  public function testArrayAccessSet() {
    // Test data.
    $data = [
      'dog' => 'cooper',
    ];

    // Create our collection with NO data.
    $data_collection = new DataCollection();

    // Set our data from our test data.
    $data_collection[key($data)] = current($data);

    // Make sure the set worked.
    $this->assertSame(current($data), $data_collection->get(key($data)));
  }

  /**
   * Test ArrayAccess isset.
   *
   * @dataProvider sampleDataProvider
   */
  public function testArrayAccessIsset($sample_data, $data_collection) {
    $this->assertTrue(isset($data_collection['id']));
    $this->assertFalse(isset($data_collection[static::$nonexistentKey]));
  }

  /**
   * Test ArrayAccess unset.
   *
   * @dataProvider sampleDataProvider
   */
  public function testArrayAccessUnset($sample_data, $data_collection) {
    $this->assertTrue(isset($data_collection['id']));

    unset($data_collection['id']);

    $this->assertFalse(isset($data_collection['id']));
  }

  /**
   * Test count.
   *
   * @dataProvider sampleDataProvider
   */
  public function testCount($sample_data, $data_collection) {
    $this->assertSame(count($sample_data), $data_collection->count());
    $this->assertGreaterThan(1, $data_collection->count());
  }

}
