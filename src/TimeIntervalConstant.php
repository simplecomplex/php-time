<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2020 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Time;

use SimpleComplex\Explorable\ExplorableInterface;
use SimpleComplex\Explorable\ExplorableBaseTrait;
use SimpleComplex\Explorable\ExplorableDumpTrait;

/**
 * Wrapped native DateInterval plus totalling props for months thru seconds.
 *
 * @see Time::diffConstant()
 *
 * Constant, not only immutable; attempting to set a property spells exception.
 *
 * All DateInterval methods inaccessible (like static createFromDateString()),
 * except for format(); which supposedly doesn't alter the DateInterval.
 * @see TimeIntervalConstant::format()
 *
 * For mutable representation, get clone of inner DateInterval.
 * @see TimeIntervalConstant::getMutable()
 *
 * (inner) \DateInterval properties:
 * @property-read int $y  Years.
 * @property-read int $m  Months.
 * @property-read int $d  Days.
 * @property-read int $h  Hours.
 * @property-read int $i  Minutes.
 * @property-read int $s  Seconds.
 * @property-read int $f  Microseconds.
 * @property-read int $invert  Zero if positive; one if negative.
 * @property-read int|bool $days  Use $totalDays instead.
 *
 * Own properties; signed totals (negative if negative diff):
 * @property-read int $totalMonths
 * @property-read int $totalDays
 * @property-read int $totalHours
 * @property-read int $totalMinutes
 * @property-read int $totalSeconds
 *
 * @package SimpleComplex\Time
 */
class TimeIntervalConstant implements ExplorableInterface
{
    use ExplorableBaseTrait;
    use ExplorableDumpTrait;

    const EXPLORABLE_VISIBLE = [
        // \DateInterval.
        'y' => null,
        'm' => null,
        'd' => null,
        'h' => null,
        'i' => null,
        's' => null,
        'f' => null,
        'invert' => null,
        'days' => null,
        // Own.
        'totalMonths' => null,
        'totalDays' => null,
        'totalHours' => null,
        'totalMinutes' => null,
        'totalSeconds' => null,
    ];

    const EXPLORABLE_HIDDEN = [];

    /**
     * @var \DateInterval
     */
    protected $dateInterval;

    /**
     * @see Time::diffConstant()
     *
     * @param \DateInterval $interval
     */
    public function __construct(\DateInterval $interval)
    {
        $this->dateInterval = $interval;
    }

    /**
     * Returns clone of the inner DateInterval.
     *
     * @return \DateInterval
     */
    public function getMutable() : \DateInterval
    {
        return clone $this->dateInterval;
    }

    /**
     * Relays to inner DateInterval's format().
     *
     * @see \DateInterval::format()
     *
     * @param string $format
     *
     * @return string
     */
    public function format(string $format) : string
    {
        return $this->dateInterval->format($format);
    }

    /**
     * Get an interval property; read-only.
     *
     * Exposes own properties and proxies to inner DateInterval's properties.
     * @see \DateInterval
     *
     * Doesn't fix DateInterval::$days; do use TimeIntervalConstant::$totalDays.
     * DateInterval::$days is false when the DateInterval wasn't created
     * via DateTimeInterface::diff().
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     *      If no such instance property.
     */
    public function __get(string $key)
    {
        if (!$this->explorableCursor) {
            $this->explorablePrepare();
        }

        if (in_array($key, $this->explorableCursor)) {
            switch ($key) {
                case 'totalMonths':
                    return (!$this->dateInterval->invert ? 1 : -1)
                        * ((int) ($this->dateInterval->format('%y') * 12) + (int) $this->dateInterval->format('%m'));
                case 'totalDays':
                case 'totalHours':
                case 'totalMinutes':
                case 'totalSeconds':
                    $sign = !$this->dateInterval->invert ? 1 : -1;
                    $days = $this->dateInterval->days;
                    // \DateInterval::days is false unless created
                    // via \DateTime::diff().
                    if ($days === false) {
                        $days = (int) $this->dateInterval->format('%a');
                    }
                    if ($key == 'totalDays') {
                        return !$days ? $days : ($sign * $days);
                    }
                    $hours = ($days * 24) + $this->dateInterval->h;
                    if ($key == 'totalHours') {
                        return !$hours ? $hours : ($sign * $hours);
                    }
                    $minutes = ($hours * 60) + $this->dateInterval->i;
                    if ($key == 'totalMinutes') {
                        return !$minutes ? $minutes : ($sign * $minutes);
                    }
                    $seconds = ($minutes * 60) + $this->dateInterval->s;
                    return !$seconds ? $seconds : ($sign * $seconds);
            }
            return $this->dateInterval->{$key};
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
        if (!$this->explorableCursor) {
            $this->explorablePrepare();
        }

        if (in_array($key, $this->explorableCursor)) {
            throw new \RuntimeException(get_class($this) . ' property[' . $key . '] is read-only.');
        }
        throw new \OutOfBoundsException(get_class($this) . ' has no property[' . $key . '].');
    }

    /**
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
                get_called_class() . ' method[' . $key . '] is forbidden because it would mutate the interval.'
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
