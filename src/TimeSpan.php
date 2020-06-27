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
     * @var TimeImmutable
     */
    protected $fromTime;

    /**
     * @var float
     */
    protected $fromUnixMicroseconds;

    /**
     *
     * @var TimeImmutable
     */
    protected $toTime;

    /**
     * @var float
     */
    protected $toUnixMicroseconds;


    /**
     * @var TimeInterval
     */
    protected $interval;

    /**
     * Difference is allowed to be zero, but not negative.
     *
     * External change to arg $fromDate or $toDate aren't reflected internally;
     * saves as TimeImmutable.
     *
     * @param \DateTimeInterface $fromDate
     * @param \DateTimeInterface $toDate
     *
     * @throws \Exception
     *      Propagated.
     */
    public function __construct(\DateTimeInterface $fromDate, \DateTimeInterface $toDate)
    {
        if ($fromDate instanceof Time) {
            $this->fromTime = $fromDate instanceof TimeImmutable ? $fromDate :
                TimeImmutable::createFromMutable($fromDate);
        }
        elseif ($fromDate instanceof \DateTimeImmutable) {
            $this->fromTime = TimeImmutable::createFromImmutable($fromDate);
        }
        else {
            $this->fromTime = TimeImmutable::createFromDateTime($fromDate);
        }
        $this->timezoneName = $this->fromTime->timezoneName;

        if ($toDate instanceof Time) {
            $this->toTime = $toDate instanceof TimeImmutable ? $toDate :
                TimeImmutable::createFromMutable($toDate);
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
                'Arg $fromDate timezone[' . $this->fromTime
                . '] differs from arg $toDate timezone[' . $this->timezoneName . '].'
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
//        $this->interval = $this->fromTime->diffTime($this->toTime);
//        if ($this->interval->totalMicroseconds < 0) {
//            throw new \InvalidArgumentException(
//                'Arg $fromDate[' . $this->fromTime . '] cannot be later than arg $toDate[' . $this->toTime . '].'
//            );
//        }
    }

    /**
     * @param TimeSpan $timeSpan
     * @return TimeInterval
     *
     * @throws \Exception
     *      Propagated.
     */
    public function diffTime(TimeSpan $timeSpan) : TimeInterval
    {
        if ($timeSpan->timezoneName != $this->timezoneName) {
            throw new \InvalidArgumentException(
                'Arg $timeSpan timezone[' . $timeSpan->timezoneName
                . '] differs from this timezone[' . $this->timezoneName . '].'
            );
        }
        return $this->toTime->diffTime($timeSpan->fromTime);
    }


    public function overlap(TimeSpan $timeSpan)
    {
        return 0;
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
