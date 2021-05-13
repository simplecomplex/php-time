<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2021 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Time;

/**
 * Difference between datetimes whose time zone has no daylight saving time.
 *
 * Only UTC datetimes are certain to qualify as subjects of such a difference.
 * PHP's \DateTimeZone offers no means of detecting if a timezone has DST.
 *
 * @package SimpleComplex\Time
 */
class TimeIntervalUnified extends TimeInterval implements Interfaces\TimeIntervalUnified
{

}
