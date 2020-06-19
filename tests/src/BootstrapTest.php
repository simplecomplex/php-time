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

// @todo
//use Psr\Container\ContainerInterface;
//use SimpleComplex\Utils\Dependency;
//use SimpleComplex\Utils\Bootstrap;

/**
 * @code
 * // CLI, in document root:
 * vendor/bin/phpunit vendor/simplecomplex/time/tests/src/BootstrapTest.php
 * @endcode
 *
 * @package SimpleComplex\Tests\Time
 */
class BootstrapTest extends TestCase
{
    protected static $booted = false;

    const TIMEZONE = 'Europe/Copenhagen';

    /*
     * Only prepares dependencies at first call.
     *
     * @return ContainerInterface
     *
    public function testDependencies()
    {
        if (!static::$booted) {
            static::$booted = true;
            Bootstrap::prepareDependenciesIfExist();
        }

        $container = Dependency::container();

        static::assertInstanceOf(ContainerInterface::class, $container);

        date_default_timezone_set(static::TIMEZONE);

        return $container;
    }
    */
}
