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

use Klein\App;
use Klein\Exceptions\DuplicateServiceException;
use Klein\Exceptions\UnknownServiceException;

/**
 * App Test.
 */
class AppTest extends AbstractKleinTest {

  /**
   * Constants.
   */
  const TEST_CALLBACK_MESSAGE = 'yay';

  /**
   * Helpers.
   */

  /**
   * Get test callable.
   */
  protected function getTestCallable($message = self::TEST_CALLBACK_MESSAGE) {
    return function () use ($message) {
      return $message;
    };
  }

  /**
   * Tests.
   */

  /**
   * Test register filter.
   */
  public function testRegisterFiller() {
    $func_name = 'yay_func';

    $app = new App();

    $app->register($func_name, $this->getTestCallable());

    $returned = $app->{$func_name}();

    $this->assertNotNull($returned);
    $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);

    return [
      'app' => $app,
      'func_name' => $func_name,
    ];
  }

  /**
   * Test get.
   *
   * @depends testRegisterFiller
   */
  public function testGet(array $args) {
    // Get our vars from our args.
    extract($args);

    $returned = $app->$func_name;

    $this->assertNotNull($returned);
    $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
  }

  /**
   * Test get bad method.
   */
  public function testGetBadMethod() {
    $this->expectException(UnknownServiceException::class);

    $app = new App();
    $app->random_thing_that_doesnt_exist;
  }

  /**
   * Test call.
   *
   * @depends testRegisterFiller
   */
  public function testCall(array $args) {
    // Get our vars from our args.
    extract($args);

    $returned = $app->{$func_name}();

    $this->assertNotNull($returned);
    $this->assertSame(self::TEST_CALLBACK_MESSAGE, $returned);
  }

  /**
   * Test call bad method.
   */
  public function testCallBadMethod() {
    $this->expectException(\BadMethodCallException::class);
    $app = new App();
    $app->random_thing_that_doesnt_exist();
  }

  /**
   * Test register duplicate method.
   *
   * @depends testRegisterFiller
   */
  public function testRegisterDuplicateMethod(array $args) {
    $this->expectException(DuplicateServiceException::class);

    // Get our vars from our args.
    extract($args);

    $app->register($func_name, $this->getTestCallable());
  }

}
