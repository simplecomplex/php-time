<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2020 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Time;

/**
 * Time which forces local (default) timezone.
 *
 * In most cases obsolete, setting timezone to local upon instantiation
 * has the same effect.
 * @see Time::setTimezoneToLocal()
 * @see Time::resolve()
 *
 * Safeguards against unexpected behaviour when creating datetime from non-PHP
 * source (like Javascript), which may serialize using UTC as timezone
 * instead of local.
 * And secures that ISO-8601 stringifiers that don't include timezone
 * information - like $dateTimeISO - behave as (presumably) expected;
 * returning values according to local timezone.
 * @see Time::$dateTimeISO
 * @see Time::getHours()
 * @see Time::timezoneIsLocal()
 *
 * @see \SimpleComplex\Time\Time
 *
 *
 * PROPERTIES inherited from Time
 * ------------------------------
 * Magically accessible properties.
 * @see Time::__get()
 *
 * Plain time parts:
 * @property-read int $year
 * @property-read int $month
 * @property-read int $date
 * @property-read int $hours
 * @property-read int $minutes
 * @property-read int $seconds
 * @property-read int $milliseconds
 * @property-read int $microseconds
 *
 * Unix Epoch:
 * Native getTimestamp() disregards microseconds; in effect floors them.
 * This property rounds microseconds.
 * @property-read int $unixSeconds
 * Floats to avoid hitting precision limit.
 * @property-read float $unixMilliseconds
 * @property-read float $unixMicroseconds
 *
 * No-zone ISO 8601 timestamps:
 * @property-read string $dateISO  YYYY-MM-DD
 * @property-read string $timeISO  HH:ii:ss
 * @property-read string $dateTimeISO  YYYY-MM-DD HH:ii:ss
 *
 * @package SimpleComplex\Time
 */
class TimeLocal extends Time
{
    /**
     * Sets timezone to local (default) upon initial construction,
     * if the timezone doesn't match local timezone.
     *
     * @param string $time
     * @param \DateTimeZone $timezone
     *
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function __construct($time = 'now', /*\DateTimeZone*/ $timezone = null)
    {
        /**
         * Parent constructor establishes local timezone,
         * and the instance's timezone name.
         * @see Time::__construct()
         * @see Time::$timezoneLocalName
         * @see Time::$timezoneName
         */
        parent::__construct($time, $timezone);
        if (!$this->timezoneIsLocal) {
            parent::setTimezone($timezone);
        }
    }

    /**
     * Errs, except if arg timezone is same as current (then ignores, noop).
     *
     * @param \DateTimeZone $timezone
     *
     * @return $this|\DateTime
     *
     * @throws \BadMethodCallException
     *      On attempt to set timezone.
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function setTimezone(/*\DateTimeZone*/ $timezone) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        $tz_name = $timezone->getName();
        if ($tz_name == $this->timezoneName) {
            // Ignore attempt to set to local timezone twice.
            return $this;
        }
        throw new \BadMethodCallException(
            'Updating ' . static::class . ' instance timezone is illegal, this timezoneName[' . $this->timezoneName
            . '] vs. arg $timezone name[' . $tz_name . '].'
        );
    }
}
