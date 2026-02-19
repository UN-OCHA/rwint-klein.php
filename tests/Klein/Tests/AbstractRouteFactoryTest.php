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

use Klein\AbstractRouteFactory;
use PHPUnit\Framework\MockObject\MockBuilder;

/**
 * Abstract Route Factory Test.
 */
class AbstractRouteFactoryTest extends AbstractKleinTest {

  /**
   * Helpers.
   */
  protected function getDefaultMethodsToMock(): array {
    return [
      'build',
    ];
  }

  /**
   * Get mock for factory.
   */
  protected function getMockForFactory(): AbstractRouteFactory {
    return $this->getMockBuilder(AbstractRouteFactory::class)
      ->onlyMethods($this->getDefaultMethodsToMock())
      ->getMock();
  }

  /**
   * Get mock builder for factory.
   */
  protected function getMockBuilderForFactory(?array $methods_to_mock = NULL): MockBuilder {
    $methods_to_mock = $methods_to_mock ?? $this->getDefaultMethodsToMock();

    return $this->getMockBuilder(AbstractRouteFactory::class)
      ->onlyMethods($methods_to_mock);
  }

  /**
   * Tests.
   */

  /**
   * Test namespace get set.
   */
  public function testNamespaceGetSet() {
    // Test data.
    $test_namespace = '/users';

    // Empty constructor.
    $factory = $this->getMockForFactory();

    $this->assertEmpty($factory->getNamespace());

    // Set in constructor.
    $factory = $this->getMockBuilderForFactory()
      ->setConstructorArgs(
        [
          $test_namespace,
        ]
      )
      ->getMock();

    $this->assertSame($test_namespace, $factory->getNamespace());

    // Set in method.
    $factory = $this->getMockForFactory();
    $factory->setNamespace($test_namespace);

    $this->assertSame($test_namespace, $factory->getNamespace());
  }

  /**
   * Test append namespace.
   */
  public function testAppendNamespace() {
    // Test data.
    $test_namespace = '/users';
    $test_namespace_append = '/names';

    $factory = $this->getMockForFactory();
    $factory->setNamespace($test_namespace);
    $factory->appendNamespace($test_namespace_append);

    $this->assertSame(
      $test_namespace . $test_namespace_append,
      $factory->getNamespace()
    );
  }

}
