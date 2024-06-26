<?php

/**
 * @file
 * Random routes.
 */

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

$this->respond(
  '/?',
  function ($request, $response, $app) {
    echo 'yup';
  }
);

$this->respond(
  '/testing/?',
  function ($request, $response, $app) {
    echo 'yup';
  }
);
