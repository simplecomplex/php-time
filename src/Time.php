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
 * Extended datetime fixing shortcomings and defects of native \DateTime.
 *
 * Features:
 * - enhanced timezone awareness
 * - diff (diffDate/diffTime, that is) works correctly with non-UTC timezone
 * - safer formatting and modifying
 * - is stringable, to ISO-8601
 * - JSON serializes to string ISO-8601 with timezone marker
 * - freezable
 * - more and simpler getters and setters
 *
 *
 * GENERAL TIMEZONE WARNING
 * Don't ever use zimezone offset() for any other purpose than pure information.
 * Computation based on offset will go wrong whenever one date is in summer time
 * and another isn't.
 *
 *
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
 *
 * @package SimpleComplex\Time
 */
class Time extends \DateTime implements \JsonSerializable
{
    /**
     * Format by time part.
     *
     * All results in integer.
     * @see Time::__get()
     *
     * @var string[]
     */
    public const TIME_PART_FORMAT = [
        'year' => 'Y',
        'month' => 'm',
        'date' => 'd',
        'hours' => 'H',
        'minutes' => 'i',
        'seconds' => 's',
        'milliseconds' => 'v',
        'microseconds' => 'u',
    ];

    /**
     * Format by time pattern.
     *
     * All results in string.
     * @see Time::__get()
     *
     * @var string[]
     */
    public const TIME_PATTERN_FORMAT = [
        'dateISO' => 'Y-m-d',
        'timeISO' => 'H:i:s',
        'dateTimeISO' => 'Y-m-d H:i:s',
    ];

    /**
     * Default subsecond precision of full ISO 8601 formats.
     *
     * @see Time::toISOZonal()
     * @see Time::toISOUTC()
     *
     * @var string
     *      Supported values: none|milli|micro.
     */
    public const ISO_SUBSECOND_PRECISION = 'micro';

    /**
     * Subsecond precision of JSON serialization format.
     *
     * @var string
     *      Supported values: none|milli|micro.
     */
    public const JSON_SUBSECOND_PRECISION = 'milli';

    /**
     * Local (default) timezone object.
     *
     * Gets established once; first time a Time object is constructed.
     * @see Time::__construct()
     *
     * Beware of changing default timezone after using a Time object.
     * @see date_default_timezone_set()
     *
     * @var \DateTimeZone
     */
    protected static $timezoneLocal;

    /**
     * Local (default) timezone name.
     *
     * 'Z' is recorded as 'UTC', to ease comparison;
     * they are considered the same.
     *
     * @var string
     */
    protected static $timezoneLocalName;

    /**
     * This object's timezone name.
     *
     * 'Z' is recorded as 'UTC', to ease comparison;
     * they are considered the same.
     *
     * @see Time::timezoneIsLocal()
     * @see Time::__construct()
     * @see Time::setTimezone()
     *
     * @var string
     */
    protected $timezoneName;

    /**
     * Whether this object's timezone is same as local (default) timezone.
     *
     * @see Time::timezoneIsLocal()
     * @see Time::__construct()
     * @see Time::setTimezone()
     *
     * @var bool|null
     */
    protected $timezoneIsLocal;

    /**
     * Freezable.
     *
     * @var bool
     */
    protected $frozen = false;


    // Methods inherited from \DateTime.----------------------------------------

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
        // Memorize local (default) timezone once and for all.
        if (!static::$timezoneLocal) {
            $time_default = new \DateTime();
            static::$timezoneLocal = $tz = $time_default->getTimezone();
            $tz_name = $tz->getName();
            static::$timezoneLocalName = $tz_name == 'Z' ? 'UTC' : $tz_name;
        }
        // Flag whether this object's timezone is same as local (default).
        $tz_name = $this->getTimezone()->getName();
        if ($tz_name == 'Z') {
            $tz_name = 'UTC';
        }
        $this->timezoneName = $tz_name;
        $this->timezoneIsLocal = $tz_name == static::$timezoneLocalName;
    }

    /**
     * @param \DateInterval $interval
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::add().
     */
    public function add(/*\DateInterval*/ $interval) : \DateTime /*self invariant*/
    {
        // NB: Argument type hinting (\DateInterval $interval)
        // would provoke E_WARNING when cloning.
        // Catch 22: Specs say that native \DateTime method is type hinted,
        // but warning when cloning says it isn't.

        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::add($interval);
    }

    /**
     * For formats, see:
     * @see http://php.net/manual/en/function.date.php
     *
     * @param string $format
     * @param string $time
     * @param \DateTimeZone|null $timezone
     *      Default: local timezone.
     *
     * @return static|Time
     *
     * @throws \Exception
     *      Propagated; \DateTime::createFromFormat().
     */
    public static function createFromFormat($format, $time, /*?\DateTimeZone*/ $timezone = null) : Time
    {
        // NB: Argument type hinting (\DateTimeZone $timezone)
        // would provoke E_WARNING.
        // Catch 22: Specs say that native method's arg $timezone is type hinted
        // \DateTimeZone, but warning when calling says it isn't.

        return static::createFromDateTime(
            parent::createFromFormat($format, $time, $timezone)
        );
    }

    /**
     * @param \DateTimeImmutable $dateTimeImmutable
     * @return Time
     *
     * @throws \Exception
     */
    public static function createFromImmutable(\DateTimeImmutable $dateTimeImmutable) : Time
    {
        /**
         * \DateTime hasn't got this method before PHP 7.3,
         * but this implementation should work anyway.
         * @see \DateTime::createFromImmutable()
         */
        return new static($dateTimeImmutable->format('Y-m-d H:i:s.u'), $dateTimeImmutable->getTimezone());
    }

    /**
     * public static function getLastErrors() : array
     * @see \DateTime::getLastErrors()
     */

    /**
     * @param string $modify
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::modify().
     */
    public function modify($modify) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::modify($modify);
    }

    /**
     * public static function __set_state(array $array) : DateTime
     * @see \DateTime::__set_state()
     */

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setDate().
     */
    public function setDate($year, $month, $day) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::setDate($year, $month, $day);
    }

    /**
     * @param int $year
     * @param int $week
     * @param int $day
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setIsoDate().
     */
    public function setISODate($year, $week, $day = 1) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::setIsoDate($year, $week, $day);
    }

    /**
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param int $microseconds
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTime()
     */
    public function setTime($hour, $minute, $second = 0, $microseconds = 0) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        // PHP 7.0 support for arg $microseconds, though ignored when PHP <7.1.
        //if (PHP_MAJOR_VERSION == 7 && !PHP_MINOR_VERSION) {
        //    return parent::setTime($hour, $minute, $second);
        //}
        return parent::setTime($hour, $minute, $second, $microseconds);
    }

    /**
     * @param int $unixtimestamp
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTimestamp().
     */
    public function setTimestamp($unixtimestamp) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::setTimestamp($unixtimestamp);
    }

    /**
     * Checks whether the object's new timezone matches local (default) timezone.
     * @see Time::timezoneIsLocal()
     *
     * @param \DateTimeZone $timezone
     *
     * @return $this|\DateTime
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function setTimezone($timezone) : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        parent::setTimezone($timezone);
        // Flag whether this object's timezone is same as local (default).
        $tz_name = $this->getTimezone()->getName();
        if ($tz_name == 'Z') {
            $tz_name = 'UTC';
        }
        $this->timezoneName = $tz_name;
        $this->timezoneIsLocal = $tz_name == static::$timezoneLocalName;
        return $this;
    }

    /**
     * @param \DateInterval $interval
     *
     * @return $this|\DateTime|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::sub().
     */
    public function sub(/*\DateInterval*/ $interval) : \DateTime /*self invariant*/
    {
        // NB: Argument type hinting (\DateInterval $interval)
        // would provoke E_WARNING when cloning.
        // Catch 22: Specs say that native \DateTime method is type hinted,
        // but warning when cloning says it isn't.

        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return parent::sub($interval);
    }

    /**
     * public function diff(\DateTimeInterface $datetime2, bool $absolute = false) : DateInterval
     * @see \DateTime::diff()
     *
     * public function format(string $format) : string
     * @see \DateTime::format()
     *
     * public function getOffset() : int
     * @see \DateTime::getOffset()
     *
     * public function getTimestamp() : int
     * @see \DateTime::getTimestamp()
     *
     * public function getTimezone() : DateTimeZone
     * @see \DateTime::getTimezone()
     *
     * public function __wakeup()
     * @see \DateTime::__wakeup()
     */


    // Own methods.-------------------------------------------------------------

    // Statics.---------------------------------------------

    /**
     * Get the local (default) timezone which gets memorized first time
     * the Time constructor gets called.
     *
     * Returns clone to prevent tampering.
     *
     * @see Time::timezoneIsLocal()
     * @see Time::$timezoneLocal
     * @see Time::$timezoneLocalName
     *
     * @return \DateTimeZone|null
     *      Null: Time constructor not called yet.
     */
    public static function getTimezoneLocalInternal() : ?\DateTimeZone
    {
        if (!static::$timezoneLocal) {
            return null;
        }
        return clone static::$timezoneLocal;
    }

    /**
     * Check that default timezone is equivalent of arg timezoneAllowed.
     *
     * Call to ensure that local default timezone is set, and accords with what
     * is expected.
     *
     * Does NOT rely on (use) the internally memorized local (default) timezone
     * object which get established first time the Time constructor gets called.
     * @see Time::$timezoneLocal
     *
     * @param string $timezoneAllowed
     *      Examples: 'UTC', 'Z', 'Europe/Copenhagen'.
     *      UTC and Z are considered the same.
     * @param bool $errOnMisMatch
     *      True: throws exception on timezone mismatch.
     *
     * @return bool
     *
     * @throws \LogicException
     *      If mismatch and arg errOnMisMatch; logic exception because
     *      considered a configuration error.
     */
    public static function checkTimezoneDefault(string $timezoneAllowed, bool $errOnMisMatch = false) : bool
    {
        $time_default = new \DateTime();
        $tz_default = $time_default->getTimezone()->getName();
        if (
            ($timezoneAllowed == 'UTC' || $timezoneAllowed == 'Z')
            && ($tz_default == 'UTC' || $tz_default == 'Z')
        ) {
            return true;
        }
        if ($tz_default != $timezoneAllowed) {
            if ($errOnMisMatch) {
                throw new \LogicException(
                    'Default timezone[' . $tz_default. '] doesn\'t match allowed timezone[' . $timezoneAllowed
                    . '], date.timezone '
                    . (!ini_get('date.timezone') ? 'not set in php.ini.' :
                        'of php.ini is \'' . ini_get('date.timezone') . '\'.'
                    )
                );
            }
            return false;
        }
        return true;
    }

    /**
     * Create from native \DateTime.
     *
     * @param \DateTimeInterface $dateTime
     *
     * @return static|Time
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public static function createFromDateTime(\DateTimeInterface $dateTime) : Time
    {
        return new static($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
    }

    /**
     * Resolves \DateTime|string|int to Time, and defaults to set timezone
     * to local (default) timezone.
     *
     * @see Time::setTimezoneToLocal()
     *
     * If arg $time is Time is will be cloned if any transformations necessary.
     *
     * Fixes that iso-8061 string from HTTP path or query argument
     * may have lost timezone + sign, due to URL encoding.
     *
     * The \DateTime constructor's fails to interprete some formats correctly.
     * These (silent) failures are not handle by this method, but notable anyway:
     * - timezone for year or year+month only (YYYYT+HH:ii/YYYY-MMT+HH:ii)
     *   produces weird offset (7 hours?)
     *
     * @param \DateTime|string|int $time
     * @param bool $keepForeignTimezone
     *      False: set to local (default) timezone.
     *
     * @return Time
     *      Identical object if arg time already is Time and no transformations
     *      necessary.
     *
     * @throws \TypeError
     * @throws \Exception
     *      Propagated; \DateTime constructor, \DateTime::setTimestamp().
     */
    public static function resolve($time, $keepForeignTimezone = false) : Time
    {
        /** @var Time $t */
        $t = null;
        if (!($time instanceof \DateTimeInterface)) {
            $subject = $time;
            if (is_string($subject)) {
                // Empty string is acceptable; \DateTime constructor
                // interpretes '' as 'now'.

                // Fix that iso-8061 from HTTP path or query argument
                // may have lost timezone + sign, due to URL encoding:
                // - minimal length: 1970-01-01T+02:00
                // - T is not supported correctly before position 10
                // - space and colon must be after T
                // - must start with 4 digits.
                if (strlen($subject) >= 17
                    && ($pos_t = strpos($subject, 'T')) && $pos_t >= 10
                    && ($pos_space = strpos($subject, ' ')) && $pos_space > $pos_t
                    && ($pos_colon = strpos($subject, ':')) && $pos_colon > $pos_t
                    && ctype_digit(substr($subject, 0, 4))
                ) {
                    $subject = str_replace(' ', '+', $subject);
                }
                $t = new static($subject);
            }
            elseif (is_int($subject)) {
                $t = (new static())->setTimestamp($subject);
            }
            else {
                throw new \TypeError(
                    'Arg $time type[' . static::getType($time) . '] is not \\DateTime|string|int.'
                );
            }
        }
        // Is \DateTime.
        elseif ($time instanceof Time) {
            if (!$keepForeignTimezone && !$time->timezoneIsLocal()) {
                return (clone $time)->setTimezoneToLocal();
            }
            return $time;
        }
        else {
            $t = static::createFromDateTime($time);
        }

        if (!$keepForeignTimezone) {
            return $t->setTimezoneToLocal();
        }
        return $t;
    }


    // Instance general.------------------------------------

    /**
     * The clone will be unfrozen.
     *
     * Time is Freezable.
     *
     * @return void
     */
    public function __clone() /*: void*/
    {
        $this->frozen = false;
        // \DateTime has no __clone() method in PHP 7.0.
        //parent::__clone();
    }

    /**
     * Helper for immutable extending class.
     *
     * @return Time
     */
    protected function cloneToMutable() : Time
    {
        return clone $this;
    }

    /**
     * Get as native \DateTime.
     *
     * @return \DateTime
     *
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function toDatetime() : \DateTime
    {
        return new \DateTime($this->format('Y-m-d H:i:s.u'), $this->getTimezone());
    }

    /**
     * Time is Freezable.
     *
     * IMPORTANT: Clones are always unfrozen.
     * @see Time::__clone()
     *
     * Chainable.
     *
     * @return $this|Time
     */
    public function freeze() /*: object*/
    {
        $this->frozen = true;
        return $this;
    }

    /**
     * Time is Freezable.
     *
     * @return bool
     */
    public function isFrozen() : bool
    {
        return $this->frozen;
    }


    // Timezone.--------------------------------------------

    /**
     * Whether this object's timezone is same as local (default) timezone.
     *
     * The ability of handling differing timezones is a blessing and a curse.
     * In Javascript the timezone aspect is simple, there's always only local
     * and UTC, and it's transparent which getters and setters work with which
     * timezone.
     * With the PHP \DateTime things are more muddled.
     * @see Time::setTimezoneToLocal()
     *
     * @return bool
     */
    public function timezoneIsLocal() : bool
    {
        return $this->timezoneIsLocal;
    }

    /**
     * Set the object's timezone to local (default), unless already local.
     *
     * Safeguards against unexpected behaviour when creating datetime
     * from non-PHP source (like Javascript), which may serialize using UTC
     * as timezone instead of local.
     * And secures that ISO-8601 stringifiers that don't include timezone
     * information - like getDateTimeISO() - behave as (presumably) expected;
     * returning values according to local timezone.
     * @see Time::getDateTimeISO()
     * @see Time::getHours()
     * @see Time::timezoneIsLocal()
     *
     * @return $this|\DateTime
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function setTimezoneToLocal() : \DateTime /*self invariant*/
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        if (!$this->timezoneIsLocal) {
            parent::setTimezone(static::$timezoneLocal);
            $this->timezoneName = static::$timezoneLocalName;
            $this->timezoneIsLocal = true;
        }
        return $this;
    }


    // Diff.------------------------------------------------

    /**
     * Works correctly with non-UTC timezones.
     *
     * Always compares using UTC timezone, because native
     * \DateTime+\DateInterval can only handle UTC reliably.
     * Uses clones, doesn't alter this or arg $dateTime's timezone.
     * @see https://bugs.php.net/bug.php?id=52480
     * @see \DateTime::diff()
     * @see \DateInterval
     *
     * Unlike diff() this has no $absolute parameter, because that doesn't
     * make sense userland (seems for internal use).
     * All native \DateInterval's properties already are absolute (unsigned),
     * so caller could just disregard the invert property.
     *
     * @param \DateTimeInterface $dateTime
     *      Supposedly equal to or later than this time,
     *      otherwise totals will be negative.
     *
     * @return \DateInterval
     *
     * @throws \RuntimeException
     *      Arg $dateTime's class has no setTimezone() method.
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function diffDate(\DateTimeInterface $dateTime) : \DateInterval
    {
        /**
         * Overriding diff() is not possible (or at least risky or ugly),
         * because this method needs to call parent diff(), and that on another
         * instance than $this.
         *
         * And PHP internals may well rely on the defects on native diff().
         */

        $tz_utc = null;

        if ($this->timezoneName == 'UTC') {
            $baseline = $this;
        } else {
            $tz_utc = new \DateTimeZone('UTC');
            $baseline = $this->cloneToMutable()->setTimezone($tz_utc);
        }

        $subject_tz_name = $dateTime->getTimezone()->getName();
        if ($subject_tz_name == 'UTC' || $subject_tz_name == 'Z') {
            $subject = $dateTime;
        }
        elseif (!method_exists($dateTime, 'setTimezone')) {
            throw new \RuntimeException(
                'Cannot diff non-UTC DateTimeInterface class[' . get_class($dateTime)
                . '] having no setTimezone method, against this Time instance.'
            );
        }
        else {
            $subject = ($dateTime instanceof Time ? $dateTime->cloneToMutable() : clone $dateTime)
                ->setTimezone($tz_utc ?? new \DateTimeZone('UTC'));
        }

        return $baseline->diff($subject);
    }

    /**
     * Get difference as a wrapped DateInterval with signed total properties,
     * including total hours, minutes etc.
     *
     * Works correctly with non-UTC timezones.
     * @see Time::diffDate()
     *
     * @param \DateTimeInterface $dateTime
     *      Supposedly equal to or later than this time,
     *      otherwise totals will be negative.
     *
     * @return TimeInterval
     *
     * @throws \RuntimeException
     *      Arg $dateTime's class has no setTimezone() method.
     * @throws \Exception
     *      Propagated; \DateTime constructor.
     */
    public function diffTime(\DateTimeInterface $dateTime) : TimeInterval
    {
        return new TimeInterval($this->diffDate($dateTime));
    }

    /**
     * @deprecated Use diffTime() instead.
     *
     * @param \DateTimeInterface $dateTime
     * @return TimeInterval
     *
     * @throws \Throwable
     *      Propagated.
     */
    public function diffConstant(\DateTimeInterface $dateTime) : TimeInterval
    {
        // Not @trigger_error() because important.
        trigger_error(
            __CLASS__ . '::' . __METHOD__
            . ' method is deprecated and will be removed soon, use diffTime instead.',
            E_USER_DEPRECATED
        );
        return $this->diffTime($dateTime);
    }


    // Modify.------------------------------------------------------------------

    /**
     * For safer date-only comparison, sets to midnight 00:00:00.000000.
     *
     * @return $this|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setTime()
     */
    public function setToDateStart() : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        return $this->setTime(0, 0, 0, 0);
    }

    /**
     * Set to first day of a month.
     *
     * @param int|null $month
     *      Null: month of this object.
     *
     * @return Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \InvalidArgumentException
     *      Arg month not null or 1 through 12.
     * @throws \Exception
     *      Propagated; \DateTime::setDate()
     */
    public function setToFirstDayOfMonth(int $month = null) : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        if ($month !== null) {
            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Arg month[' . $month . '] isn\'t null or 1 through 12.');
            }
            $mnth = $month;
        }
        else {
            $mnth = (int) $this->format('m');
        }
        return $this->setDate(
            (int) $this->format('Y'),
            $mnth,
            1
        );
    }

    /**
     * Set to last day of a month.
     *
     * @param int|null $month
     *      Null: month of this object.
     *
     * @return Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \InvalidArgumentException
     *      Arg month not null or 1 through 12.
     * @throws \Exception
     *      Propagated; \DateTime::setDate()
     */
    public function setToLastDayOfMonth(int $month = null) : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        if ($month !== null) {
            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Arg month[' . $month . '] isn\'t null or 1 through 12.');
            }
            $mnth = $month;
        }
        else {
            $mnth = (int) $this->format('m');
        }
        return $this->setDate(
            (int) $this->format('Y'),
            $mnth,
            $this->monthLengthDays($mnth)
        );
    }

    /**
     * Unlike \Datetime::modify() this throws exception on failure.
     *
     * \Datetime::modify() emits warning and returns false on failure.
     *
     * @param string $modify
     *
     * @return $this|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \InvalidArgumentException
     *      Arg format invalid.
     * @throws \Exception
     *      Propagated; \DateTime::modify().
     */
    public function modifySafely(string $modify) : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }
        $modified = $this->modify($modify);
        if (($modified instanceof \DateTime)) {
            return $this;
        }
        throw new \InvalidArgumentException('Arg modify[' . $modify . '] is invalid.');
    }

    /**
     * Add to or subtract from one or more date parts.
     *
     * Validity adjustment when arg years:
     * If current date is February 29th and target year isn't a leap year,
     * then target date becomes February 28th.
     *
     * Validity adjustment when arg months:
     * If current month is longer than target month and current day
     * doesn't exist in target month, then target day becomes last day
     * of target month.
     *
     * These validity adjustments are equivalent with database adjustments
     * like MySQL::date_add() and MSSQL::dateadd().
     *
     * Native \DateTime::modify():
     * - is difficult to use and it's format argument isn't documented
     * - makes nonsensical year|month addition/subtraction
     * - doesn't throw exception on failure
     *
     * @see \DateTime::modify()
     *
     * @param int $years
     *      Subtracts if negative.
     * @param int $months
     *      Subtracts if negative.
     * @param int $days
     *      Subtracts if negative.
     *
     * @return $this|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::setDate(), \DateTime::modify().
     */
    public function modifyDate(int $years, int $months = 0, int $days = 0) : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }

        if ($years) {
            $year = (int) $this->format('Y');
            $month = (int) $this->format('m');
            $day = (int) $this->format('d');
            // Validity adjustment when part is year:
            // If current date is February 29th and target year isn't
            // a leap year, then target date becomes February 28th.
            // Target date is February 29th and target year isn't leap year.
            if ($month == 2 && $day == 29 && !date('L', mktime(1, 1, 1, 2, 1, $year + $years))) {
                $day = 28;
            }
            $this->setDate($year + $years, $month, $day);
        }

        if ($months) {
            $target_year = $year = (int) $this->format('Y');
            $month = (int) $this->format('m');
            $day = (int) $this->format('d');
            $target_month = $month + $months;
            if ($target_month > 12) {
                $add_years = (int) floor($target_month / 12);
                $target_month -= $add_years * 12;
                $target_year += $add_years;
            }
            elseif ($target_month < 1) {
                $subtract_years = (int) ceil(-$target_month / 12);
                $target_month += $subtract_years * 12;
                $target_year -= $subtract_years;
            }
            if (!$target_month) {
                $target_month = 12;
                --$target_year;
            }
            // Validity adjustment when part is month:
            // If current month is longer than target month and current
            // day doesn't exist in target month, then target day
            // becomes last day of target month.
            if ($day > 28) {
                $max_day = $target_year == $year ? $this->monthLengthDays($target_month) :
                    $this->monthLengthDays($target_month, $target_year);
                if ($day > $max_day) {
                    $day = $max_day;
                }
            }
            $this->setDate($target_year, $target_month, $day);
        }

        if ($days) {
            $this->modify(($days > 0 ? '+' : '-') . abs($days) . ' ' . (abs($days) > 1 ? 'days' : 'day'));
        }

        return $this;
    }

    /**
     * Add to or subtract from one or more time parts.
     *
     * Native \DateTime::modify():
     * - is difficult to use and it's format argument isn't documented
     * - doesn't throw exception on failure
     *
     * @param int $hours
     *      Subtracts if negative.
     * @param int $minutes
     *      Subtracts if negative.
     * @param int $seconds
     *      Subtracts if negative.
     * @param int $microseconds
     *      Subtracts if negative.
     *      Ignored when PHP 7.0 (<7.1).
     *
     * @return $this|Time
     *
     * @throws \RuntimeException
     *      Frozen.
     * @throws \Exception
     *      Propagated; \DateTime::modify().
     */
    public function modifyTime(int $hours, int $minutes = 0, int $seconds = 0, int $microseconds = 0) : Time
    {
        if ($this->frozen) {
            throw new \RuntimeException(get_class($this) . ' is read-only, frozen.');
        }

        $modifiers = [];
        if ($hours) {
            $modifiers[] = ($hours > 0 ? '+' : '-') . abs($hours) . ' ' . (abs($hours) > 1 ? 'hours' : 'hour');
        }
        if ($minutes) {
            $modifiers[] = ($minutes > 0 ? '+' : '-') . abs($minutes) . ' ' . (abs($minutes) > 1 ? 'minutes' : 'minute');
        }
        if ($seconds) {
            $modifiers[] = ($seconds > 0 ? '+' : '-') . abs($seconds) . ' ' . (abs($seconds) > 1 ? 'seconds' : 'second');
        }
        if ($microseconds && (PHP_MAJOR_VERSION != 7 || PHP_MINOR_VERSION)) {
            $modifiers[] = ($microseconds > 0 ? '+' : '-') . abs($microseconds)
                . ' ' . (abs($microseconds) > 1 ? 'microseconds' : 'microsecond');
        }
        if ($modifiers) {
            $this->modify(join(' ', $modifiers));
        }
        return $this;
    }


    // Informational.---------------------------------------

    /**
     * @param int $year
     *      Default: year of this object.
     *
     * @return bool
     */
    public function isLeapYear(int $year = null) : bool
    {
        return !!date(
            'L',
            $year === null ? $this->getTimestamp() : mktime(1, 1, 1, 2, 1, $year)
        );
    }

    /**
     * Number of days in a month of year.
     *
     * @param int|null $month
     *      Null: month of this object.
     * @param int|null $year
     *      Null: year of this object.
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     *      Arg month not 1 through 12.
     */
    public function monthLengthDays(int $month = null, int $year = null) : int
    {
        $mnth = $month ?? $this->format('m');
        switch ($mnth) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                return 31;
            case 4:
            case 6:
            case 9:
            case 11:
                return 30;
            case 2:
                return !date('L', $year === null ? $this->getTimestamp() : mktime(1, 1, 1, 2, 1, $year)) ? 28 : 29;
        }
        throw new \InvalidArgumentException('Arg month[' . $month . '] is not 1 through 12.');
    }


    // Formatting getters.----------------------------------

    /**
     * Unlike \Datetime::format() this throws exception on failure.
     *
     * \Datetime::format() emits warning and returns false on failure.
     *
     * Can unfortunately not simply override Datetime::format() because that
     * sends native \DateTime operations into perpetual loop.
     *
     * @param string $format
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *      Arg format invalid.
     */
    public function formatSafely(string $format) : string
    {
        $v = $this->format($format);
        if (is_string($v)) {
            return $v;
        }
        throw new \InvalidArgumentException('Arg format[' . $format . '] is invalid.');
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     *      No such property.
     */
    public function __get(string $key)
    {
        switch ($key) {
            case 'unixSeconds':
                return (int) round(
                    $this->getTimestamp() + ((int) $this->format('u') / 1000000)
                );
            case 'unixMilliseconds':
                // Uses the 'u' format instead of 'v' for consistency with
                // toUnixMicroseconds().
                return (float) ($this->getTimestamp() * 1000) + round($this->format('u') / 1000, 3);
            case 'unixMicroseconds':
                return (float) ($this->getTimestamp() * 1000000) + (float) $this->format('u');
        }
        /**
         * Final; self not static.
         * @see Time::TIME_PART_FORMAT
         */
        $format = self::TIME_PART_FORMAT[$key] ?? null;
        if ($format) {
            return (int) $this->format($format);
        }
        /**
         * Final; self not static.
         * @see Time::TIME_PATTERN_FORMAT
         */
        $format = self::TIME_PATTERN_FORMAT[$key] ?? null;
        if ($format) {
            return $this->format($format);
        }

        throw new \OutOfBoundsException(get_class($this) . ' instance exposes no property[' . $key . '].');
    }

    /**
     * Handles methods which existed in simplecomplex/utils(time),
     * now deprecated:
     * - getYear, getMonth, getDate, getHours, getMinutes, getSeconds,
     *   getMilliseconds, getMicroseconds
     * - getDateISO, getTimeISO, getDateTimeISO
     *
     * Relays to
     * @see Time::__get()
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     *      No such method.
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            /**
             * Final; self not static.
             * @see Time::TIME_PART_FORMAT
             */
            $parts = array_keys(self::TIME_PART_FORMAT);
            foreach ($parts as $part) {
                $method = 'get' . ucfirst($part);
                if ($name == $method) {
                    // Not @trigger_error() because important.
                    trigger_error(
                        __CLASS__ . '::' . $name . ' method is deprecated and will be removed soon'
                        . ', use instance property[' . $part . '] instead.',
                        E_USER_DEPRECATED
                    );
                    return $this->__get($part);
                }
            }
            /**
             * Final; self not static.
             * @see Time::TIME_PATTERN_FORMAT
             */
            $patterns = array_keys(self::TIME_PATTERN_FORMAT);
            foreach ($patterns as $pattern) {
                $method = 'get' . ucfirst($pattern);
                if ($name == $method) {
                    // Not @trigger_error() because important.
                    trigger_error(
                        __CLASS__ . '::' . $name . ' method is deprecated and will be removed soon'
                        . ', use instance property[' . $pattern . '] instead.',
                        E_USER_DEPRECATED
                    );
                    return $this->__get($pattern);
                }
            }
        }

        throw new \BadMethodCallException( 'Class ' . __CLASS__ . ' has no method[' . $name . '].');
    }

    /**
     * Format to Y-m-d, using local (default) timezone.
     *
     * Does not alter the object's own timezone.
     *
     * @return string
     *
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function toDateISOLocal() : string
    {
        if ($this->timezoneIsLocal) {
            return $this->format('Y-m-d');
        }
        return $this->cloneToMutable()->setTimezone(static::$timezoneLocal)->format('Y-m-d');
    }

    /**
     * Format to H:i:s, using local (default) timezone.
     *
     * Does not alter the object's own timezone.
     *
     * @return string
     *
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function toTimeISOLocal() : string
    {
        if ($this->timezoneIsLocal) {
            return $this->format('H:i:s');
        }
        return $this->cloneToMutable()->setTimezone(static::$timezoneLocal)->format('H:i:s');
    }

    /**
     * Format to Y-m-d H:i:s, using local (default) timezone.
     *
     * Does not alter the object's own timezone.
     *
     * @return string
     *
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function toDateTimeISOLocal() : string
    {
        if ($this->timezoneIsLocal) {
            return $this->format('Y-m-d H:i:s');
        }
        return $this->cloneToMutable()->setTimezone(static::$timezoneLocal)->format('Y-m-d H:i:s');
    }

    /**
     * To ISO-8601 with timezone marker.
     *
     * Formats:
     * YYYY-MM-DDTHH:ii:ss+HH:II
     * YYYY-MM-DDTHH:ii:ss.mmm+HH:II
     * YYYY-MM-DDTHH:ii:ss.mmmmmm+HH:II
     *
     * @see Time::ISO_SUBSECOND_PRECISION
     * @see Time::__toString().
     *
     * @param string|null $subSecondPrecision
     *      Values: none|milli|micro.
     *      Default: static::ISO_SUBSECOND_PRECISION.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *      Arg precision not empty or milliseconds|microseconds.
     */
    public function toISOZonal(?string $subSecondPrecision = null) : string
    {
        $precision = $subSecondPrecision ?? static::ISO_SUBSECOND_PRECISION;

        $str = $this->format('c');
        switch ($precision) {
            case 'micro':
            case 'microseconds':
                // 'microseconds' for backwards compatibility.
                $minor = '.' . $this->format('u');
                break;
            case 'milli':
            case 'milliseconds':
                // 'milliseconds' for backwards compatibility.
                $minor = '.' . $this->format('v');
                break;
            case 'none':
            case '':
                // '' for backwards compatibility.
                return $str;
            default:
                throw new \InvalidArgumentException(
                    'Arg subSecondPrecision[' . $subSecondPrecision . '] isn\'t none|milli|micro.'
                );
        }
        return substr($str, 0, -6) . $minor . substr($str, -6);
    }

    /**
     * To ISO-8601 UTC.
     *
     * Formats:
     * YYYY-MM-DDTHH:ii:ssZ
     * YYYY-MM-DDTHH:ii:ss.mmmZ
     * YYYY-MM-DDTHH:ii:ss.mmmmmmZ
     *
     * Like Javascript Date.toISOString(); when milliseconds precision.
     *
     * @see Time::ISO_SUBSECOND_PRECISION
     *
     * @param string|null $subSecondPrecision
     *      Values: none|milli|micro.
     *      Default: static::ISO_SUBSECOND_PRECISION.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     *      Arg precision not empty or milliseconds|microseconds.
     * @throws \Exception
     *      Propagated; \DateTime::setTimezone().
     */
    public function toISOUTC(?string $subSecondPrecision = null) : string
    {
        $precision = $subSecondPrecision ?? static::ISO_SUBSECOND_PRECISION;

        switch ($precision) {
            case 'micro':
            case 'microseconds':
                // 'microseconds' for backwards compatibility.
                $minor = '.' . $this->format('u');
                break;
            case 'milli':
            case 'milliseconds':
                // 'milliseconds' for backwards compatibility.
                $minor = '.' . $this->format('v');
                break;
            case 'none':
            case '':
                // '' for backwards compatibility.
                $minor = '';
                break;
            default:
                throw new \InvalidArgumentException(
                    'Arg subSecondPrecision[' . $subSecondPrecision . '] isn\'t none|milli|micro.'
                );
        }
        return substr(
                $this->cloneToMutable()->setTimezone(new \DateTimeZone('UTC'))->format('c'),
                0,
                -6
            ) . $minor . 'Z';
    }

    /**
     * Format to YYYY-MM-DDTHH:ii:ss.mmmmmm+HH:II
     *
     * Same as:
     * @see Time::toISOZonal().
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toISOZonal();
    }

    /**
     * JSON serializes to string ISO-8601 with timezone marker.
     *
     * Unlike native \DateTime which JSON serializes to object;
     * which is great when communicating with other PHP base node,
     * but a nuisance when communicating with anything else.
     *
     * @see Time::JSON_SUBSECOND_PRECISION
     * @see Time::toISOZonal()
     * @see \JsonSerializable
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toISOZonal(static::JSON_SUBSECOND_PRECISION);
    }

    /**
     * Get subject class name or (non-object) type.
     *
     * Counter to native gettype() this method returns:
     * - class name instead of 'object'
     * - 'float' instead of 'double'
     * - 'null' instead of 'NULL'
     *
     * Like native gettype() this method returns:
     * - 'boolean' not 'bool'
     * - 'integer' not 'int'
     * - 'unknown type' for unknown type
     *
     * @param mixed $subject
     *
     * @return string
     */
    protected static function getType($subject)
    {
        if (!is_object($subject)) {
            $type = gettype($subject);
            switch ($type) {
                case 'double':
                    return 'float';
                case 'NULL':
                    return 'null';
                default:
                    return $type;
            }
        }
        return get_class($subject);
    }
}
