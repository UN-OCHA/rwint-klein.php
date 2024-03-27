<?php

/**
 * @file
 * Tests boostrap.
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

// Set some configuration values.
// Don't send headers when testing sessions.
ini_set('session.use_cookies', 0);
// Don't send cache headers when testing sessions.
ini_set('session.cache_limiter', '');

// Load our autoloader, and add our Test class namespace.
$autoloader = require __DIR__ . '/../vendor/autoload.php';
$autoloader->add('Klein\Tests', __DIR__);

// Load our functions bootstrap.
require __DIR__ . '/functions-bootstrap.php';
