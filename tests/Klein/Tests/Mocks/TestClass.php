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

namespace Klein\Tests\Mocks;

/**
 * Test class.
 */
class TestClass {

  /**
   * Get.
   *
   * @param \Klein\Request $request
   *   Request.
   * @param \Klein\Respone $response
   *   Response.
   * @param \Klein\App $app
   *   App.
   *
   * @return mixed
   *   Something.
   */
  public static function get($request, $response, $app) {
    echo 'ok';
  }

}
