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
 * Immutable Time.
 *
 * Uses intermediate Time instances for mutations.
 *
 * Freezing has no effect, but is allowed.
 * @see Time::freeze()
 * @see Time::frozen()
 *
 *
 * Extends Time, not \DateTimeImmutable.
 * Thus Time and TimeImmutable are both:
 * - Time
 * - \DateTime
 * - \DateTimeInterface
 *
 * \DateTime and \DateTimeImmutable are separate classes, but both implement
 * \DateTimeInterface.
 *
 * The main reason not to extend DateTimeImmutable is that it would increase
 * the number of shortcomings and defects to be handled uncontrollably.
 * DateTimeImmutable is most likely even more flawed than DateTime, e.g.:
 * @see https://www.php.net/manual/en/class.datetimeimmutable.php#123543
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
class TimeImmutable extends Time
{
    /**
     * DateTimeImmutable extension would be achievable by using traits;
     * in Time and here.
     * If so, beware that DateTimeImmutable's mutational methods don't use
     * new static(), thus all it's mutational methods must be overridden:
     * @see https://www.php.net/manual/en/class.datetimeimmutable.php#123543
     */


    // Methods inherited ultimately from \DateTime.-----------------------------

    /**
     * Checks whether the new object's timezone matches local (default) timezone.
     *
     * Memorizes local (default) timezone first time called.
     * @see Time::timezoneIsLocal()
     *
     * @param string $time
     * @param \DateTimeZone|null $timezone
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function __construct($time = 'now', /*\DateTimeZone*/ $timezone = null)
    {
        parent::__construct($time, $timezone);
    }

    /**
     * {@inheritDoc}
     */
    public function add(/*\DateInterval*/ $interval) : \DateTime /*self invariant*/
    {
        // Use intermediate Time; cloning would result in perpetual loop.
        $t = $this->cloneToMutable()->add($interval);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * @see Time::createFromFormat()
     */

    /**
     * @param \DateTime $dateTime
     * @return Time
     *
     * @throws \Exception
     */
    public static function createFromMutable(\DateTime $dateTime) : Time
    {
        // Time::createFromDateTime() uses new static(),
        return parent::createFromDateTime($dateTime);
    }

    /**
     * public static function getLastErrors() : array
     * @see \DateTime::getLastErrors()
     */

    /**
     * {@inheritDoc}
     */
    public function modify($modify) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->modify($modify);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * public static function __set_state(array $array) : DateTime
     * @see \DateTime::__set_state()
     */

    /**
     * {@inheritDoc}
     */
    public function setDate($year, $month, $day) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setDate($year, $month, $day);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setISODate($year, $week, $day = 1) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setIsoDate($year, $week, $day);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setTime($hour, $minute, $second = 0, $microseconds = 0) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setTime($hour, $minute, $second, $microseconds);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setTimestamp($unixtimestamp) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setTimestamp($unixtimestamp);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setTimezone($timezone) : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setTimezone($timezone);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function sub(/*\DateInterval*/ $interval) : \DateTime /*self invariant*/
    {
        // NB: Argument type hinting (\DateInterval $interval)
        // would provoke E_WARNING when cloning.
        // Catch 22: Specs say that native \DateTime method is type hinted,
        // but warning when cloning says it isn't.

        $t = $this->cloneToMutable()->sub($interval);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }


    // Methods inherited from Time.---------------------------------------------

    // Statics.---------------------------------------------

    /**
     * {@inheritDoc}
     */
    public static function resolve($time, $keepForeignTimezone = false) : Time
    {
        if ($time instanceof Time) {
            if ($time instanceof TimeImmutable) {
                if (!$keepForeignTimezone && !$time->timezoneIsLocal()) {
                    // TimeImmutable::setTimezoneToLocal() returns new.
                    return $time->setTimezoneToLocal();
                }
                return $time;
            }
            if (!$keepForeignTimezone && !$time->timezoneIsLocal()) {
                $t = (clone $time)->setTimezoneToLocal();
                return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
            }
            return new static($time->format('Y-m-d H:i:s.u'), $time->getTimezone());
        }
        /**
         * Let inherited handle:
         * - \DateTime
         * - not \DateTimeInterface; string|int
         * And Time::resolve() uses new static().
         * @see Time::resolve()
         */
        return parent::resolve($time, $keepForeignTimezone);
    }


    // Instance general.------------------------------------

    /**
     * Helper for immutable extending class (like this).
     *
     * @return Time
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    protected function cloneToMutable() : Time
    {
        return new Time($this->format('Y-m-d H:i:s.u'), $this->getTimezone());
    }

    /**
     * Get as Time.
     *
     * @return Time
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function toTime() : Time
    {
        return $this->cloneToMutable();
    }

    /**
     * Does nothing, TimeImmutable is not Freezable,
     * despite child class of Time.
     *
     * Freezing would have no effect.
     * Instance self is never mutated, uses intermediate Time instances
     * for mutations.
     *
     * @return $this|TimeImmutable
     */
    public function freeze() /*: object*/
    {
        return $this;
    }

    /**
     * @see Time::frozen()
     */

    // Timezone.--------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function setTimezoneToLocal() : \DateTime /*self invariant*/
    {
        $t = $this->cloneToMutable()->setTimezoneToLocal();
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }


    // Diff.------------------------------------------------

    // None.

    // Modify.------------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function setToDateStart() : Time
    {
        $t = $this->cloneToMutable()->setToDateStart();
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setToFirstDayOfMonth(int $month = null) : Time
    {
        $t = $this->cloneToMutable()->setToFirstDayOfMonth();
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function setToLastDayOfMonth(int $month = null) : Time
    {
        $t = $this->cloneToMutable()->setToLastDayOfMonth();
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function modifySafely(string $modify) : Time
    {
        $t = $this->cloneToMutable()->modifySafely($modify);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function modifyDate(int $years, int $months = 0, int $days = 0) : Time
    {
        $t = $this->cloneToMutable()->modifyDate($years, $months = 0, $days);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }

    /**
     * {@inheritDoc}
     */
    public function modifyTime(int $hours, int $minutes = 0, int $seconds = 0, int $microseconds = 0) : Time
    {
        $t = $this->cloneToMutable()->modifyTime($hours, $minutes = 0, $seconds, $microseconds);
        return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
    }
}
