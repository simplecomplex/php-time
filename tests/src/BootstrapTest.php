<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2020 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Tests\Time;

use PHPUnit\Framework\TestCase;

/**
 * @code
 * // CLI, in document root:
 * vendor/bin/phpunit --do-not-cache-result vendor/simplecomplex/time/tests/src/BootstrapTest.php
 * @endcode
 *
 * @package SimpleComplex\Tests\Time
 */
class BootstrapTest extends TestCase
{
    const TIMEZONE = 'Europe/Copenhagen';
}
