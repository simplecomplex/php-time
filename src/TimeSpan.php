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
 *
 * @todo: The 'period' name is already occupied, by \DatePeriod
 *
 *
 * @property-read string $timezoneName
 * @property-read TimeImmutable $fromTime
 * @property-read TimeImmutable $toTime
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
     * @var Time
     */
    protected $fromTime;

    /**
     * @var float
     */
    protected $fromUnixMicroseconds;

    /**
     *
     * @var Time
     */
    protected $toTime;

    /**
     * @var float
     */
    protected $toUnixMicroseconds;

    /**
     * Difference is allowed to be zero, but not negative.
     *
     * Saves references to arg $fromDate, $toDate if they are Time instances;
     * otherwise saves equivalent Time|TimeImmutable objects.
     * @see TimeSpan::$fromTime
     * @see TimeSpan::$toTime
     *
     * @param \DateTimeInterface $fromDate
     * @param \DateTimeInterface $toDate
     *      Must be equal to or later than arg $fromDate.
     *      Timezone must be equal to $fromDate's.
     *
     * @throws \Exception
     *      Propagated.
     */
    public function __construct(\DateTimeInterface $fromDate, \DateTimeInterface $toDate)
    {
        // @todo: there's no reason to convert to Time.


        if ($fromDate instanceof Time) {
            $this->fromTime = $fromDate;
        }
        elseif ($fromDate instanceof \DateTimeImmutable) {
            $this->fromTime = TimeImmutable::createFromImmutable($fromDate);
        }
        else {
            $this->fromTime = TimeImmutable::createFromDateTime($fromDate);
        }
        $this->timezoneName = $this->fromTime->timezoneName;

        if ($toDate instanceof Time) {
            $this->toTime = $toDate;
        }
        elseif ($toDate instanceof \DateTimeImmutable) {
            $this->toTime = TimeImmutable::createFromImmutable($toDate);
        }
        else {
            $this->toTime = Time::createFromDateTime($toDate);
        }

        $to_tz = $this->toTime->timezoneName;
        if ($to_tz != $this->timezoneName) {
            throw new \InvalidArgumentException(
                'Arg $toDate timezone[' . $to_tz
                . '] differs from arg $fromDate timezone[' . $this->timezoneName . '].'
            );
        }

        $this->fromUnixMicroseconds = $this->fromTime->unixMicroseconds;
        $this->toUnixMicroseconds = $this->fromTime->unixMicroseconds;

        // Allowed to be same, because may represent a period of a single date
        // despite same time of day.
        if ($this->fromUnixMicroseconds > $this->toUnixMicroseconds) {
            throw new \InvalidArgumentException(
                'Arg $fromDate[' . $this->fromTime . '] cannot be later than arg $toDate[' . $this->toTime . '].'
            );
        }
    }

    /**
     * @return TimeInterval
     *
     * @throws \Exception
     *      Propagated.
     */
    public function interval() : TimeInterval
    {
        return $this->fromTime->diffTime($this->toTime);
    }

    /**
     * Measures diff between this toTime and that fromTime.
     *
     * @param TimeSpan $timeSpan
     * @return TimeInterval
     *
     * @throws \Exception
     *      Propagated.
     */
    public function diffTimeSpan(TimeSpan $timeSpan) : TimeInterval
    {
        if ($timeSpan->timezoneName != $this->timezoneName) {
            throw new \InvalidArgumentException(
                'Arg $timeSpan timezone[' . $timeSpan->timezoneName
                . '] differs from this timezone[' . $this->timezoneName . '].'
            );
        }
        return $this->toTime->diffTime($timeSpan->fromTime);
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
     * Check whether another TimeSpan overlaps this.
     *
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
     */
    public function overlap(TimeSpan $timeSpan)
    {
        $subject_from = $timeSpan->fromTime->unixMicroseconds;
        $subject_to = $timeSpan->toTime->unixMicroseconds;

        if ($subject_to >= $this->fromUnixMicroseconds && $subject_from <= $this->toUnixMicroseconds) {
            // Identity.
            if ($subject_from == $this->fromUnixMicroseconds && $subject_to == $this->toUnixMicroseconds) {
                return static::OVERLAP_IDENTITY;
            }
            // Encloses this.
            elseif ($subject_from <= $this->fromUnixMicroseconds && $subject_to >= $this->toUnixMicroseconds) {
                return static::OVERLAP_ENCLOSES;
            }
            // Subset of this.
            elseif ($subject_from >= $this->fromUnixMicroseconds && $subject_to <= $this->toUnixMicroseconds) {
                return static::OVERLAP_IS_SUBSET;
            }
            // Ends within this.
            elseif ($subject_to < $this->toUnixMicroseconds) {
                return static::OVERLAP_ENDS_WITHIN;
            }
            // Begins within this.
            elseif ($subject_from > $this->fromUnixMicroseconds) {
                return static::OVERLAP_BEGINS_WITHIN;
            }
            else {
                throw new \LogicException(
                    __CLASS__ . '::' . __METHOD__ . '() fails to explain how '
                    . $timeSpan->fromTime . ' - ' . $timeSpan->toTime
                    . ' overlaps ' . $this->fromTime . ' - ' . $this->toTime . '.'
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
            case 'fromTime':
            case 'toTime':
                return $this->{$key};
        }
        throw new \OutOfBoundsException(get_class($this) . ' instance exposes no property[' . $key . '].');
    }
}
