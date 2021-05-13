<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2021 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Time\Interfaces;

/**
 * Habitual difference across daylight saving time shift.
 *
 * If the timezone's daylight saving time is 1 hour, then the difference
 * between a winter midnight and a summer midnight is:
 * - hours off by zero
 * - days off by zero
 *
 * @see \SimpleComplex\Time\Interfaces\TimeInterval::$relativeHours
 * @see \SimpleComplex\Time\Interfaces\TimeInterval::$totalHours
 * @see \SimpleComplex\Time\Interfaces\TimeInterval::$totalDays
 *
 * @package SimpleComplex\Time
 */
interface TimeIntervalHabitual extends TimeInterval
{

}
