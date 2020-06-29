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
 * Time span, aka period, primarily usable for checking overlap among periods.
 *
 * Doesn't extend native \DatePeriod since purpose slightly different,
 * and because incompatible with \DatePeriod constructor (whose second parameter
 * is \DateInterval).
 * @see \DatePeriod::__construct()
 * @see TimeSpan::__construct()
 *
 * A TimeSpan always includes both ends; no option to exclude the beginning,
 * like \DatePeriod::EXCLUDE_START_DATE.
 *
 * Is in effect frozen/immutable, because saves or refers the datetimes
 * internally as TimeImmutables.
 * @see TimeImmutable
 * @see TimeSpan::$from
 * @see TimeSpan::$to
 *
 * @property-read string $timezoneName
 * @property-read TimeImmutable $from
 * @property-read TimeImmutable $to
 * @property-read float $fromUnixMicroseconds
 * @property-read float $toUnixMicroseconds
 *
 * @package SimpleComplex\Time
 */
class TimeSpan
{
    /**
     * @var string
     */
    protected $timezoneName;

    /**
     * @var TimeImmutable
     */
    protected $from;

    /**
     * @var float
     */
    protected $fromUnixMicroseconds;

    /**
     *
     * @var TimeImmutable
     */
    protected $to;

    /**
     * @var float
     */
    protected $toUnixMicroseconds;

    /**
     * Difference between $from and $to is allowed to be zero, but not negative.
     *
     * The two datetimes must be in same timezone.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     *      Must be equal to or later than arg $from.
     *      Timezone must be equal to $from's.
     *
     * @throws \InvalidArgumentException
     *      Args $from and $to are in differing timezones.
     *      Arg $from later than arg $to.
     * @throws \Exception
     *      Propagated.
     */
    public function __construct(\DateTimeInterface $from, \DateTimeInterface $to)
    {
        $this->from = $from instanceof TimeImmutable ? $from : TimeImmutable::createFromDateTime($from);
        $this->timezoneName = $this->from->timezoneName;
        $this->to = $to instanceof TimeImmutable ? $to : TimeImmutable::createFromDateTime($to);

        if ($this->to->timezoneName != $this->timezoneName) {
            throw new \InvalidArgumentException(
                'Arg $to timezone[' . $this->to->timezoneName
                . '] differs from arg $from timezone[' . $this->timezoneName . '].'
            );
        }

        // There's a cost to producing unixMicroseconds,
        // thus saved once and for all.
        $this->fromUnixMicroseconds = $this->from->unixMicroseconds;
        $this->toUnixMicroseconds = $this->to->unixMicroseconds;

        // Allowed to be same, because may represent a period of a single date
        // despite same time of day.
        if ($this->fromUnixMicroseconds > $this->toUnixMicroseconds) {
            throw new \InvalidArgumentException(
                'Arg $from[' . $this->from . '] cannot be later than arg $to[' . $this->to . '].'
            );
        }
    }

    /**
     * Difference between this from and this to.
     *
     * @return TimeInterval
     *
     * @throws \Exception
     *      Propagated.
     */
    public function timeInterval() : TimeInterval
    {
        return $this->from->diffTime($this->to);
    }

    /**
     * Difference between this to and arg $timeSpan from,
     * or this from and arg $timeSpan to (negative).
     *
     * @param TimeSpan $timeSpan
     * @return TimeInterval|int
     *      Int: Arg $timeSpan overlaps this.
     *
     * @throws \InvalidArgumentException
     *      Arg $timeSpan is in other timezone than this.
     * @throws \Exception
     *      Propagated.
     */
    public function diffTimeSpan(TimeSpan $timeSpan)
    {
        if ($timeSpan->timezoneName != $this->timezoneName) {
            throw new \InvalidArgumentException(
                'Arg $timeSpan timezone[' . $timeSpan->timezoneName
                . '] differs from this timezone[' . $this->timezoneName . '].'
            );
        }

        $overlap = $this->overlap($timeSpan);
        if ($overlap != static::OVERLAP_NONE) {
            return $overlap;
        }

        if ($timeSpan->fromUnixMicroseconds > $this->toUnixMicroseconds) {
            return $this->to->diffTime($timeSpan->from);
        }
        // Negative.
        return $this->from->diffTime($timeSpan->to);
    }

    /**
     * @var int
     */
    public const OVERLAP_NONE = 0;

    /**
     * @var int
     */
    public const OVERLAP_IDENTITY = 1;

    /**
     * @var int
     */
    public const OVERLAP_ENCLOSES = 2;

    /**
     * @var int
     */
    public const OVERLAP_IS_SUBSET = 3;

    /**
     * @var int
     */
    public const OVERLAP_ENDS_WITHIN = 4;

    /**
     * @var int
     */
    public const OVERLAP_BEGINS_WITHIN = 5;

    /**
     * Checks whether another TimeSpan overlaps this.
     *
     * Explains overlap, returning one of these (int) class constants:
     * @see TimeSpan::OVERLAP_NONE
     * @see TimeSpan::OVERLAP_IDENTITY
     * @see TimeSpan::OVERLAP_ENCLOSES
     * @see TimeSpan::OVERLAP_IS_SUBSET
     * @see TimeSpan::OVERLAP_ENDS_WITHIN
     * @see TimeSpan::OVERLAP_BEGINS_WITHIN
     *
     * @param TimeSpan $timeSpan
     *
     * @return int
     *
     * @throws \LogicException
     *      Algo error in this method; failure to explain overlap.
     * @throws \Exception
     *      Propagated.
     */
    public function overlap(TimeSpan $timeSpan)
    {
        $baseline_from = $this->fromUnixMicroseconds;
        $baseline_to = $this->toUnixMicroseconds;
        $subject_from = $timeSpan->fromUnixMicroseconds;
        $subject_to = $timeSpan->toUnixMicroseconds;

        if ($subject_to >= $baseline_from && $subject_from <= $baseline_to) {
            // Identity.
            if ($subject_from == $baseline_from && $subject_to == $baseline_to) {
                return static::OVERLAP_IDENTITY;
            }
            // Encloses this.
            elseif ($subject_from <= $baseline_from && $subject_to >= $baseline_to) {
                return static::OVERLAP_ENCLOSES;
            }
            // Subset of this.
            elseif ($subject_from >= $baseline_from && $subject_to <= $baseline_to) {
                return static::OVERLAP_IS_SUBSET;
            }
            // Ends within this.
            elseif ($subject_to < $baseline_to) {
                return static::OVERLAP_ENDS_WITHIN;
            }
            // Begins within this.
            elseif ($subject_from > $baseline_from) {
                return static::OVERLAP_BEGINS_WITHIN;
            }
            else {
                throw new \LogicException(
                    __CLASS__ . '::' . __METHOD__ . '() fails to explain how '
                    . $timeSpan->from . ' - ' . $timeSpan->to . ' overlaps ' . $this->from . ' - ' . $this->to . '.'
                );
            }
        }

        return static::OVERLAP_NONE;
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
            case 'timezoneName':
            case 'from':
            case 'to':
            case 'fromUnixMicroseconds':
            case 'toUnixMicroseconds':
                return $this->{$key};
        }
        throw new \OutOfBoundsException(get_class($this) . ' instance exposes no property[' . $key . '].');
    }
}
