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
 * Mock \DateInterval plus signed totalling properties.
 *
 * Works for non-UTC timezones - when instantiated by diffTime().
 * Native diff()+DateInterval doesn't get time parts right, when non-UTC
 * and the datetimes aren't both outside or within summer time.
 * @see https://bugs.php.net/bug.php?id=52480
 * @see Time::diffTime()
 *
 *
 * All properties are read-only.
 * Native \DateInterval's properties are writable, but with no effect:
 * - setting $d doesnt't update $days
 * - setting $days doesn't update $days (and no error)
 * Pretty weird design indeed.
 *
 *
 * All DateInterval methods inaccessible except for format().
 * @see \DateInterval::format()
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
 * Own properties; all signed:
 * @property-read int $relativeYears
 * @property-read int $relativeMonths
 * @property-read int $relativeDays
 * @property-read int $relativeHours
 * @property-read int $relativeMinutes
 * @property-read int $relativeSeconds
 * @property-read float $relativeMicroseconds
 * @property-read int $totalYears
 * @property-read int $totalMonths
 * @property-read int $totalDays
 * @property-read int $totalHours
 * @property-read int $totalMinutes
 * @property-read int $totalSeconds
 * @property-read float $totalMicroseconds
 *
 * @property-read string $durationISO
 *
 * @package SimpleComplex\Time
 */
class TimeInterval
{
    /**
     * Why not simply extend \DateInterval?
     * ------------------------------------
     * Because the (total) $days property would become false, since the instance
     * couldn't be created directly via \DateTimeInterface::diff().
     * Us knowing the appropriate value of $days is no help here, because
     * the days property cannot be overwritten.
     *
     * So it's a choice between two evils:
     * i. A \DateInterval extension who deceptively looks right (instanceof),
     * but somewhat crippled (days:false).
     * ii. A mock DateInterval which works fairly right,
     * but may not be accepted (not instanceof).
     *
     * Not absolutely sure if this is the right choice.
     */


    // Native properties of verbatim diff() \DateInterval.

    /**
     * Years, unsigned.
     *
     * @var int
     */
    protected $y;

    /**
     * Relative months, unsigned.
     *
     * @var int
     */
    protected $m;

    /**
     * Relative days, unsigned.
     *
     * @var int
     */
    protected $d;


    // Native properties of truly UTC diff() \DateInterval.

    /**
     * Relative hours, unsigned.
     *
     * @var int
     */
    protected $h;

    /**
     * Relative minutes, unsigned.
     *
     * @var int
     */
    protected $i;

    /**
     * Relative seconds, unsigned.
     *
     * @var int
     */
    protected $s;

    /**
     * Relative microseconds, unsigned.
     *
     * @var float
     */
    protected $f;

    /**
     * 0 for positive difference, 1 for negative.
     *
     * @var int
     */
    protected $invert;

    /**
     * Total days, unsigned.
     *
     * @var int
     */
    protected $days;


    // Our properties of verbatim diff() \DateInterval.

    /**
     * Years, (y) signed.
     *
     * @var int
     */
    protected $relativeYears;

    /**
     * Relative months, (m) signed.
     *
     * @var int
     */
    protected $relativeMonths;

    /**
     * Relative days, (d) signed.
     *
     * @var int
     */
    protected $relativeDays;

    /**
     * Years, (y) signed.
     *
     * Same as
     * @see TimeInterval::$relativeYears
     *
     * @var int
     */
    protected $totalYears;

    /**
     * Total months, (y + m) signed.
     *
     * @var int
     */
    protected $totalMonths;


    // Our properties of truly UTC diff() \DateInterval.

    /**
     * Relative hours, (h) signed.
     *
     * @var int
     */
    protected $relativeHours;

    /**
     * Relative minutes, (i) signed.
     *
     * @var int
     */
    protected $relativeMinutes;

    /**
     * Relative seconds, (s) signed.
     *
     * @var int
     */
    protected $relativeSeconds;

    /**
     * Relative seconds, (f) signed.
     *
     * @var float
     */
    protected $relativeMicroseconds;

    /**
     * Total days, (days) signed.
     *
     * @var int
     */
    protected $totalDays;

    /**
     * Total hours, (days + h) signed.
     *
     * @var int
     */
    protected $totalHours;

    /**
     * Total hours, (days + h + i) signed.
     *
     * @var int
     */
    protected $totalMinutes;

    /**
     * Total hours, (days + h + i + s) signed.
     *
     * @var int
     */
    protected $totalSeconds;

    /**
     * Total microseconds, (days + h + i + s + f) signed.
     *
     * @var float
     */
    protected $totalMicroseconds;

    /**
     * ISO 8601 duration.
     *
     * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
     *
     * @var string
     */
    protected $durationISO;


    /**
     * Needs/uses two native diff()/DateInterval when timezone not UTC.
     *
     * Years, months and relative days
     * -------------------------------
     * Requires datetimes that have verbatim time parts like the originals.
     * If the original datetimes were non-UTC, they must now have been set
     * artificially to UTC. But doing so, absolute days and hours, minutes,
     * seconds will be off.
     *
     * Absolute days, and hours, minutes, seconds
     * ------------------------------------------
     * Requires datetimes who are - or have been moved to - UTC.
     * \DateTimeInterface::setTimezone() works for this, it moves the time parts
     * along with the timezone.
     * But doing so, makes years, months and relative days off.
     *
     * @see Time::diffTime()
     *
     * @param \DateInterval $intervalUTC
     *      Interval between datetimes originally in, or set to, UTC.
     * @param \DateInterval|null $intervalVerbatim
     *      Interval of equivalents, with verbatim time of non-UTC originals.
     *
     * @throws \InvalidArgumentException
     *      Arg $interval or $intervalVerbatim not a \DateInterval constructed
     *      via \DateTimeInterface::diff().
     */
    public function __construct(\DateInterval $intervalUTC, ?\DateInterval $intervalVerbatim = null)
    {
        if ($intervalUTC->days === false) {
            throw new \InvalidArgumentException(
                'Arg $intervalUTC is not a \DateInterval constructed via \DateTimeInterface::diff().'
            );
        }
        if ($intervalVerbatim && $intervalVerbatim->days === false) {
            throw new \InvalidArgumentException(
                'Arg $intervalVerbatim is not a \DateInterval constructed via \DateTimeInterface::diff().'
            );
        }

        $sign = $intervalUTC->invert ? -1 : 1;

        /**
         * Years, months and relative days
         * -------------------------------
         * Requires datetimes that have verbatim time parts like the originals.
         * If the original datetimes were non-UTC, they must now have been set
         * artificially to UTC. But doing so, total days and hours, minutes,
         * seconds will be off.
         */
        if ($intervalVerbatim) {
            $this->y = $intervalVerbatim->y;
            $this->m = $intervalVerbatim->m;
            $this->d = $intervalVerbatim->d;
        }
        else {
            $this->y = $intervalUTC->y;
            $this->m = $intervalUTC->m;
            $this->d = $intervalUTC->d;
        }
        $this->totalYears = $this->relativeYears = $sign * $this->y;
        $this->relativeMonths = $sign * $this->m;
        $this->relativeDays = $sign * $this->d;
        $this->totalMonths = ($this->totalYears * 12) + $this->relativeMonths;

        /**
         * Total days, and hours, minutes, seconds
         * ------------------------------------------
         * Requires datetimes who are - or have been moved to - UTC.
         * \DateTimeInterface::setTimezone() works for this, it moves the time parts
         * along with the timezone.
         * But doing so, makes years, months and relative days off.
         */
        $this->h = $intervalUTC->h;
        $this->i = $intervalUTC->i;
        $this->s = $intervalUTC->s;
        $this->f = $intervalUTC->f;
        $this->invert = $intervalUTC->invert;
        $this->days = $intervalUTC->days;

        $this->relativeHours = $sign * $this->h;
        $this->relativeMinutes = $sign * $this->i;
        $this->relativeSeconds = $sign * $this->s;
        $this->relativeMicroseconds = $sign * $this->f;

        $this->totalDays = $sign * $this->days;
        $this->totalHours = ($this->totalDays * 24) + ($sign * $this->h);
        $this->totalMinutes = ($this->totalHours * 60) + ($sign * $this->i);
        $this->totalSeconds = ($this->totalMinutes * 60) + ($sign * $this->s);
        $this->totalMicroseconds = ($this->totalSeconds + ($sign * $this->f)) * 1000000;

        // P1Y1M1DT1H1M1S.
        $this->durationISO = 'P'
            // $intervalVerbatim.
            . $this->y . 'Y' . $this->m . 'M' . $this->d . 'DT'
            // $intervalUTC.
            . $this->h . 'H' . $this->i . 'M' . $this->s . 'S';
    }

    /**
     * Returns \DateInterval representation.
     *
     * @see TimeInterval::$durationISO
     *
     * Not constructed via \DateTimeInterface constructor,
     * thus it's $days property is false.
     *
     * @return \DateInterval
     *
     * @throws \Exception
     *      Propagated; \DateInterval constructor.
     */
    public function getDateInterval() : \DateInterval
    {
        return new \DateInterval($this->durationISO);
    }

    /**
     * @deprecated Use getDateInterval() instead.
     *
     * @return \DateInterval
     *
     * @throws \Exception
     *      Propagated; \DateInterval constructor.
     */
    public function getMutable() : \DateInterval
    {
        // Not @trigger_error() because important.
        trigger_error(
            __CLASS__ . '::' . __METHOD__
            . ' method is deprecated and will be removed soon, use getDateInterval instead.',
            E_USER_DEPRECATED
        );
        return $this->getDateInterval();
    }

    /**
     * @param string $format
     *
     * @return string
     *
     * @throws \Exception
     *      Propagated; \DateInterval::format().
     */
    public function format(string $format) : string
    {
        return $this->getDateInterval()->format($format);
    }

    /**
     * Get an interval property; read-only.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     *      If no such instance property.
     * @throws \LogicException
     *      Internal \DateInterval is not a constructed
     *      via \DateTimeInterface::diff().
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }
        throw new \OutOfBoundsException(get_class($this) . ' has no property[' . $key . '].');
    }

    /**
     * @param string $key
     * @param mixed|null $value
     *
     * @return void
     *
     * @throws \OutOfBoundsException
     *      If no such instance property.
     * @throws \RuntimeException
     *      If that instance property is read-only.
     */
    public function __set(string $key, $value)
    {
        if (property_exists($this, $key)) {
            throw new \RuntimeException(get_class($this) . ' property[' . $key . '] is read-only.');
        }
        throw new \OutOfBoundsException(get_class($this) . ' has no property[' . $key . '].');
    }

    /**
     * Make var_dump() make sense.
     *
     * @return array
     */
    public function __debugInfo() : array
    {
        return get_object_vars($this);
    }

    /**
     * \DateInterval instance methods not proxied by this class.
     *
     * @param string $key
     * @param $arguments
     *
     * @throws \RuntimeException
     *      Always.
     */
    public function __call(string $key, $arguments)
    {
        throw new \RuntimeException(
            get_class($this) . ' method[' . $key . '] doesn\'t exist, ' . (
                !method_exists(\DateInterval::class, $key) ? 'nor has native \DateInterval such method.' :
                    'despite that native \DateInterval has that method.'
            )
        );
    }

    /**
     * \DateInterval static methods not proxied by this class.
     *
     * @param string $key
     * @param $arguments
     *
     * @throws \RuntimeException
     *      Always.
     */
    public static function __callStatic(string $key, $arguments)
    {
        if ($key == 'createFromDateString') {
            throw new \RuntimeException(
                get_called_class() . '::' . $key
                . '() is forbidden because the internal \DateInterval must be created via \DateTimeInterface::diff().'
            );
        }
        throw new \RuntimeException(
            get_called_class() . ' method[' . $key . '] doesn\'t exist, ' . (
            !method_exists(\DateInterval::class, $key) ? 'nor has native \DateInterval such method.' :
                'despite that native \DateInterval has that method.'
            )
        );
    }
}
