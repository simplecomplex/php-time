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
 * Mock \DateInterval plus signed totalling properties.
 *
 * \DateInterval equivalent properties:
 * @property-read int $y  Years.
 * @property-read int $m  Months.
 * @property-read int $d  Days.
 * @property-read int $h  Hours.
 * @property-read int $i  Minutes.
 * @property-read int $s  Seconds.
 * @property-read int $f  Microseconds.
 * @property-read int $invert  Zero if positive; one if negative.
 * @property-read int $days  Use $totalDays instead.
 *
 * Signed synonyms of \DateInterval properties y, m, d,h, i, s, f:
 * @property-read int $relativeYears
 * @property-read int $relativeMonths
 * @property-read int $relativeDays
 * @property-read int $relativeHours
 * @property-read int $relativeMinutes
 * @property-read int $relativeSeconds
 * @property-read float $relativeMicros
 *
 * Signed totals:
 * @property-read int $totalYears
 * @property-read int $totalMonths
 * @property-read int $totalDays
 * @property-read int $totalHours
 * @property-read int $totalMinutes
 * @property-read int $totalSeconds
 * @property-read float $totalMicros
 *
 * @property-read string $ISODuration
 *
 * @see \SimpleComplex\Time\TimeInterval
 *
 * @package SimpleComplex\Time
 */
interface TimeInterval
{
    /**
     * Returns \DateInterval representation.
     *
     * @return \DateInterval
     */
    public function toDateInterval() : \DateInterval;

    /**
     * @param string $format
     *
     * @return string
     *
     * @see \DateInterval::format()
     */
    public function format(string $format) : string;

}
