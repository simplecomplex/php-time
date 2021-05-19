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
 * Actual difference across daylight saving time shift.
 *
 * If the timezone's daylight saving time is 1 hour, then the difference
 * between a winter midnight and a summer midnight is:
 * - hours off by 1 or 23
 * - days off by 1
 *
 * @see \SimpleComplex\Time\TimeInterval::$relativeHours
 * @see \SimpleComplex\Time\TimeInterval::$totalHours
 * @see \SimpleComplex\Time\TimeInterval::$totalDays
 *
 * @package SimpleComplex\Time
 */
class TimeIntervalActual extends TimeInterval implements Interfaces\TimeIntervalActual
{

}
