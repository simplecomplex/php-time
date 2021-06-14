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
 * Formally this class should really _not_ extend the 'DST ignorant' class,
 * but instead implement a 'unified' interface which extends 'actual'
 * and 'DST ignorant' interfaces.
 * However, as long as there aren't more time-interval variants that would
 * be overkill IMHO.
 *
 * @see Time::diffTime()
 * @see Time::diffDstIgnorant()
 *      This class doesn't in itself differ from it's parents, the difference
 *      lies in the way the Time diff method prepares constructor arguments.
 *
 * @package SimpleComplex\Time
 */
class TimeIntervalUnified extends TimeIntervalDstIgnorant
{
}
