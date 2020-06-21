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
 * Extends Time, not \DateTimeImmutable.
 *
 * DateTimeImmutable extension would be achievable by using traits;
 * in Time and here.
 * DateTimeImmutable doesn't extend DateTime. And it's mutational
 * methods doesn't use new static().
 * @see https://www.php.net/manual/en/class.datetimeimmutable.php#123543
 *
 * @package SimpleComplex\Time
 */
class TimeImmutable extends Time
{
    /**
     * @var Time
     */
    protected $timeInner;


    // Inherited methods.-------------------------------------------------------

    /**
     * Checks whether the new object's timezone matches local (default) timezone.
     *
     * Memorizes local (default) timezone first time called.
     * @see Time::timezoneIsLocal()
     *
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @param Time|null $clonedMutableTime
     *      Time: all other args ignored, and circumvents all checks.
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function __construct($time = 'now', /*\DateTimeZone*/ $timezone = null, Time $clonedMutableTime = null)
    {
        if ($clonedMutableTime) {
            $iso = $clonedMutableTime->format('Y-m-d H:i:s.u');
            $zone = $clonedMutableTime->getTimezone();

            parent::__construct($iso, $zone);

            if ($clonedMutableTime instanceof TimeImmutable) {
                $this->timeInner = new Time($iso, $zone);
            }
            else {
                // Faster than construction.
                $this->timeInner = $clonedMutableTime;
            }
        }
        else {
            parent::__construct($time, $timezone);
            $this->timeInner = $mutableTime ?? new Time($this->format('Y-m-d H:i:s.u'), $this->getTimezone());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function add(/*\DateInterval*/ $interval) : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->add($interval);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
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
        $t = (clone $this->timeInner)->modify($modify);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
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
        $t = (clone $this->timeInner)->setDate($year, $month, $day);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setISODate($year, $week, $day = 1) : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->setIsoDate($year, $week, $day);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setTime($hour, $minute, $second = 0, $microseconds = 0) : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->setTime($hour, $minute, $second, $microseconds);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setTimestamp($unixtimestamp) : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->setTimestamp($unixtimestamp);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setTimezone($timezone) : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->setTimezone($timezone);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
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

        $t = (clone $this->timeInner)->sub($interval);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }


    // Own methods.-------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public static function resolve($time, $keepForeignTimezone = false) : Time
    {
        if ($time instanceof Time) {
            if ($time instanceof TimeImmutable) {
                $t = (clone $time);
                if (!$keepForeignTimezone && !$time->timezoneIsLocal()) {
                    $t->setTimezoneToLocal();
                }
                return $t;
            }
            if (!$keepForeignTimezone && !$time->timezoneIsLocal()) {
                $t = (clone $time)->setTimezoneToLocal();
                //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
                return new static('', null, $t);
            }
            //return new static($time->format('Y-m-d H:i:s.u'), $time->getTimezone());
            $t = (clone $time);
            return new static('', null, $t);
        }
        return parent::resolve($time, $keepForeignTimezone);
    }

    /**
     * {@inheritDoc}
     */
    public function setTimezoneToLocal() : \DateTime /*self invariant*/
    {
        $t = (clone $this->timeInner)->setTimezoneToLocal();
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
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
        return clone $this->timeInner;
    }

    /**
     * {@inheritDoc}
     */
    public function modifySafely(string $modify) : Time
    {
        $t = (clone $this->timeInner)->modifySafely($modify);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setToDateStart() : Time
    {
        $t = (clone $this->timeInner)->setToDateStart();
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setToFirstDayOfMonth(int $month = null) : Time
    {
        $t = (clone $this->timeInner)->setToFirstDayOfMonth();
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function setToLastDayOfMonth(int $month = null) : Time
    {
        $t = (clone $this->timeInner)->setToLastDayOfMonth();
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyDate(int $years, int $months = 0, int $days = 0) : Time
    {
        $t = (clone $this->timeInner)->modifyDate($years, $months = 0, $days);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     */
    public function modifyTime(int $hours, int $minutes = 0, int $seconds = 0, int $microseconds = 0) : Time
    {
        $t = (clone $this->timeInner)->modifyTime($hours, $minutes = 0, $seconds, $microseconds);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function setJsonSerializePrecision(string $precision) : Time
    {
        $t = (clone $this->timeInner)->setJsonSerializePrecision($precision);
        //return new static($t->format('Y-m-d H:i:s.u'), $t->getTimezone());
        return new static('', null, $t);
    }
}
