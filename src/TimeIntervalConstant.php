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
 * Wrapped native DateInterval plus totalling props for years thru seconds.
 *
 * @see Time::diffConstant()
 *
 * The 'Constant' part of this class' name is stupid, since native \DateInterval
 * is constant too. Didn't realize that DateInterval's 'public' properties
 * were readonly (and php.net documentation doesn't give any such hint either).
 *
 * All DateInterval methods inaccessible except for format().
 * @see TimeIntervalConstant::format()
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
 * @property-read int $days  Use $totalDays instead.
 *
 * Own properties; signed totals (negative if negative diff):
 * @property-read int $totalYears
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
    /**
     * Why not simply extend \DateInterval?
     * Because the (total) $days property would become false, since the instance
     * couldn't be created directly via \DateTimeInterface::diff().
     * And us knowing the appropriate value of $days is no help here, because
     * the days property cannot be overwritten (not truly protected).
     *
     * So it's a choice between two evils:
     * i. A \DateInterval extension who deceptively looks right (instanceof),
     * but somewhat crippled (days:false).
     * ii. A mock DateInterval which works right,
     * but may not be accepted (not instanceof).
     *
     * Not sure if this is the right choice.
     */

    use ExplorableBaseTrait;
    use ExplorableDumpTrait;

    /**
     * @see \SimpleComplex\Explorable\Explorable
     */
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
        'totalYears' => null,
        'totalMonths' => null,
        'totalDays' => null,
        'totalHours' => null,
        'totalMinutes' => null,
        'totalSeconds' => null,
    ];

    /**
     * @see \SimpleComplex\Explorable\Explorable
     */
    const EXPLORABLE_HIDDEN = [];

    /**
     * @var \DateInterval
     */
    protected $dateInterval;

    /**
     * @see Time::diffConstant()
     *
     * @param \DateInterval $interval
     *
     * @throws \InvalidArgumentException
     *      Arg $interval not a \DateInterval constructed via
     *      \DateTimeInterface::diff().
     */
    public function __construct(\DateInterval $interval)
    {
        if ($interval->days === false) {
            throw new \InvalidArgumentException(
                'Arg $interval is not a \DateInterval constructed via \DateTimeInterface::diff().'
            );
        }
        $this->dateInterval = $interval;
    }

    /**
     * @deprecated Use getDateInterval() instead.
     *
     * @return \DateInterval
     */
    public function getMutable() : \DateInterval
    {
        return clone $this->dateInterval;
    }

    /**
     * Returns clone of the inner DateInterval.
     *
     * @return \DateInterval
     */
    public function getDateInterval() : \DateInterval
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
        if (!$this->explorableCursor) {
            $this->explorablePrepare();
        }

        if (in_array($key, $this->explorableCursor)) {
            $sign = !$this->dateInterval->invert ? 1 : -1;
            switch ($key) {
                case 'totalYears':
                case 'totalMonths':
                    $years = $sign * $this->dateInterval->y;
                    if ($key == 'totalYears') {
                        return $years;
                    }
                    return ($years * 12) + ($sign * $this->dateInterval->m);
                case 'totalDays':
                case 'totalHours':
                case 'totalMinutes':
                case 'totalSeconds':
                    /**
                     * DateInterval->days is _total_ days (years + months + days),
                     * whereas DateInterval->d is _net_ days (years and months excluded).
                     *
                     * Thus years and months shan't be added when calculating
                     * total days, hours, minutes and seconds.
                     *
                     * @see \DateInterval::$days
                     * @see \DateInterval::$d
                     */
                    $days = $this->dateInterval->days;
                    // \DateInterval::days is false unless created
                    // via \DateTime|\DateTimeImmutable::diff().
                    if ($days === false) {
                        throw new \LogicException(
                            'Internal \DateInterval is not a constructed via \DateTimeInterface::diff().'
                        );
                    }
                    $days *= $sign;
                    if ($key == 'totalDays') {
                        return $days;
                    }
                    $hours = ($days * 24) + ($sign * $this->dateInterval->h);
                    if ($key == 'totalHours') {
                        return $hours;
                    }
                    $minutes = ($hours * 60) + ($sign * $this->dateInterval->i);
                    if ($key == 'totalMinutes') {
                        return $minutes;
                    }
                    return ($minutes * 60) + ($sign * $this->dateInterval->s);
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
