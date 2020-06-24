<?php
/**
 * SimpleComplex PHP Time
 * @link      https://github.com/simplecomplex/php-time
 * @copyright Copyright (c) 2017-2020 Jacob Friis Mathiasen
 * @license   https://github.com/simplecomplex/php-time/blob/master/LICENSE (MIT License)
 */
declare(strict_types=1);

namespace SimpleComplex\Tests\Time;

use PHPUnit\Framework\TestCase;
use Jasny\PHPUnit\ExpectWarningTrait;

use SimpleComplex\Time\Time;
use SimpleComplex\Time\TimeImmutable;

/**
 * @code
 * // CLI, in document root:
 * vendor/bin/phpunit --do-not-cache-result vendor/simplecomplex/time/tests/src/TimeTest.php
 * @endcode
 *
 * @package SimpleComplex\Tests\Time
 */
class TimeTest extends TestCase
{
    /**
     * Continue on PHP notice|warning; detect E_USER_DEPRECATED.
     * @see https://github.com/jasny/phpunit-extension
     * @see https://github.com/sebastianbergmann/phpunit/issues/3758
     */
    use ExpectWarningTrait;

    /**
     * @see \SimpleComplex\Time\Time::validateTimezoneDefault()
     *
     */
    public function testValidateTimezoneDefault()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $timezone_default = date_default_timezone_get();
        static::assertTrue(Time::checkTimezoneDefault($timezone_default));

        $tz_default = (new Time())->getTimezone()->getName();
        if ($tz_default == 'UTC' || $tz_default == 'Z') {
            static::assertTrue(Time::checkTimezoneDefault('UTC'));
            static::assertFalse(Time::checkTimezoneDefault(BootstrapTest::TIMEZONE));
            $this->expectException(\LogicException::class);
            Time::checkTimezoneDefault(BootstrapTest::TIMEZONE, true);
        } else {
            static::assertFalse(Time::checkTimezoneDefault('UTC'));
            $this->expectException(\LogicException::class);
            Time::checkTimezoneDefault('UTC', true);
        }
    }

    public function testMagicProperties()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        //error_reporting(E_ALL | E_DEPRECATED | E_USER_DEPRECATED);

        $times = [
            'Time' => new Time('2020-06-15 12:37:59.555555'),
            'TimeImmutable' => new TimeImmutable('2020-06-15 12:37:59.555555'),
        ];

        foreach ($times as $class => $time) {
            static::assertIsInt($time->year, '(' . $class . ')->year integer');
            static::assertSame(2020, $time->year, '(' . $class . ')->year value');
            static::assertSame(6, $time->month, '(' . $class . ')->month value');
            static::assertSame(15, $time->date, '(' . $class . ')->date value');
            static::assertSame(12, $time->hours, '(' . $class . ')->hours value');
            static::assertSame(37, $time->minutes, '(' . $class . ')->minutes value');
            static::assertSame(59, $time->seconds, '(' . $class . ')->seconds value');
            static::assertSame(555, $time->milliseconds, '(' . $class . ')->milliseconds value');
            static::assertSame(555555, $time->microseconds, '(' . $class . ')->microseconds value');
            // Deprecated getYear() etc.
            $this->expectDeprecation();
            static::assertIsInt($time->{'getYear'}(), '(' . $class . ')->year integer');
            $this->expectDeprecation();
            static::assertSame(2020, $time->{'getYear'}(), '(' . $class . ')->year value');
            $this->expectDeprecation();
            static::assertSame(6, $time->{'getMonth'}(), '(' . $class . ')->month value');
            $this->expectDeprecation();
            static::assertSame(15, $time->{'getDate'}(), '(' . $class . ')->date value');
            $this->expectDeprecation();
            static::assertSame(12, $time->{'getHours'}(), '(' . $class . ')->hours value');
            $this->expectDeprecation();
            static::assertSame(37, $time->{'getMinutes'}(), '(' . $class . ')->minutes value');
            $this->expectDeprecation();
            static::assertSame(59, $time->{'getSeconds'}(), '(' . $class . ')->seconds value');
            $this->expectDeprecation();
            static::assertSame(555, $time->{'getMilliseconds'}(), '(' . $class . ')->milliseconds value');
            $this->expectDeprecation();
            static::assertSame(555555, $time->{'getMicroseconds'}(), '(' . $class . ')->microseconds value');
        }
    }

    /**
     * @see \SimpleComplex\Time\Time::modifyDate()
     */
    public function testModifyDate()
    {
        $time = new Time('2018-01-01');

        $years = $months = $days = 1;
        static::assertSame('2018-01-01', (clone $time)->modifyDate(0, 0)->dateISO);
        static::assertSame('2019-02-02', (clone $time)->modifyDate($years, $months, $days)->dateISO);
        // 2017-01-01
        // 2016-12-01
        // 2016-11-30
        static::assertSame('2016-11-30', (clone $time)->modifyDate(-$years, -$months, -$days)->dateISO);

        // Modifying month only.------------------------------------------------
        $log = [];

        $year = 2018;
        $month = 1;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = 25;
        $log[] = '';
        $log[] = '     ' . $time->dateISO;
        for ($months = 1; $months <= $limit; ++$months) {
            $yr = $year;
            $mnth = $month;
            if ($months < 12) {
                $mnth += $months;
            }
            elseif ($months < 24) {
                $yr += 1;
                $mnth += ($months - 12);
            }
            else {
                $yr += 2;
                $mnth += ($months - 24);
            }
            static::assertSame(
                ($yr)
                . '-' . str_pad('' . $mnth, 2, '0', STR_PAD_LEFT)
                . '-' . str_pad('' . ($day), 2, '0', STR_PAD_LEFT),
                $result = (clone $time)->modifyDate(0, $months)->dateISO
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 12;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = -25;
        $log[] = '';
        $log[] = '     ' . $time->dateISO;
        for ($months = -1; $months >= $limit; --$months) {
            $yr = $year;
            $mnth = $month;
            if ($months > -12) {
                $mnth += $months;
            }
            elseif ($months > -24) {
                $yr -= 1;
                $mnth += ($months + 12);
            }
            else {
                $yr -= 2;
                $mnth += ($months + 24);
            }
            static::assertSame(
                ($yr)
                . '-' . str_pad('' . $mnth, 2, '0', STR_PAD_LEFT)
                . '-' . str_pad('' . ($day), 2, '0', STR_PAD_LEFT),
                $result = (clone $time)->modifyDate(0, $months)->dateISO
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 7;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = 25;
        $log[] = '';
        $log[] = '     ' . $time->dateISO;
        for ($months = 1; $months <= $limit; ++$months) {
            $yr = $year;
            $mnth = $month;
            if ($months < 6) {
                $mnth += $months;
            }
            elseif ($months < 18) {
                $yr += 1;
                $mnth += ($months - 12);
            }
            else {
                $yr += 2;
                $mnth += ($months - 24);
            }
            static::assertSame(
                ($yr)
                . '-' . str_pad('' . $mnth, 2, '0', STR_PAD_LEFT)
                . '-' . str_pad('' . ($day), 2, '0', STR_PAD_LEFT),
                $result = (clone $time)->modifyDate(0, $months)->dateISO
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 7;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = -25;
        $log[] = '';
        $log[] = '     ' . $time->dateISO;
        for ($months = -1; $months >= $limit; --$months) {
            $yr = $year;
            $mnth = $month;
            if ($months >= -6) {
                $mnth += $months;
            }
            elseif ($months >= -18) {
                $yr -= 1;
                $mnth += ($months + 12);
            }
            else {
                $yr -= 2;
                $mnth += ($months + 24);
            }
            static::assertSame(
                ($yr)
                . '-' . str_pad('' . $mnth, 2, '0', STR_PAD_LEFT)
                . '-' . str_pad('' . ($day), 2, '0', STR_PAD_LEFT),
                $result = (clone $time)->modifyDate(0, $months)->dateISO
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }


        // /Modifying month only.-----------------------------------------------

        // Days only.
        $time = new Time('2018-01-01');
        static::assertSame('2018-01-02', (clone $time)->modifyDate(0, 0, 1)->dateISO);

        // Last day of February.
        $time = new Time('2018-01-31');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, 1)->dateISO);
        // Leap year last day of February.
        static::assertSame('2020-02-29', (clone $time)->modifyDate(2, 1)->dateISO);

        // Last day of February.
        $time = new Time('2018-01-01');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, 1)->setToLastDayOfMonth()->dateISO);
        $time = new Time('2018-03-31');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, -1)->dateISO);


        $time = new Time('2018-01-01');
        static::assertSame('2018-02-20', (clone $time)->modifyDate(0, 0, 50)->dateISO);
    }

    /**
     * @see \SimpleComplex\Time\Time::modifyTime()
     */
    public function testModifyTime()
    {
        $time = new Time('2018-01-01 15:37:13');
        static::assertSame('2018-01-01 16:38:14', (clone $time)->modifyTime(1, 1, 1)->dateTimeISO);
        static::assertSame('2018-01-02 16:38:14', (clone $time)->modifyTime(25, 1, 1)->dateTimeISO);
        static::assertSame('2017-12-31 14:36:12', (clone $time)->modifyTime(-25, -1, -1)->dateTimeISO);
    }

    /**
     * @see \SimpleComplex\Time\Time::diffTime()
     */
    public function testDiffTime()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $first = (new Time('2019-02-01'))->setToDateStart();
        $last = (new Time('2019-03-01'))->setToDateStart();

        $interval_mutable = $first->diff($last);
        static::assertSame(0, $interval_mutable->h, '');
        $interval_mutable->h = 2;
        static::assertSame(2, $interval_mutable->h, '');

        $interval_constant = $first->diffTime($last);
        static::assertSame(0, $interval_constant->h, '');
        $this->expectException(\RuntimeException::class);
        /** @noinspection Annotator */
        $interval_constant->{'h'} = 2;

        /**
         * \SimpleComplex\Time\Time::diffTime()
         *
         * Fixes that native diff()|\DateInterval calculation doesn't work correctly
         * with other timezone than UTC.
         * @see https://bugs.php.net/bug.php?id=52480
         */

        $tz_default = date_default_timezone_get();

        date_default_timezone_set('UTC');
        $first = (new Time('2019-02-01'))->setToDateStart();
        $last = (new Time('2019-03-01'))->setToDateStart();
        $diff = $first->diffTime($last);
        static::assertSame(1, $diff->totalMonths);

        // This would fail if that bug wasn't handled.
        date_default_timezone_set(BootstrapTest::TIMEZONE);
        $first = (new Time('2019-02-01'))->setToDateStart();
        $last = (new Time('2019-03-01'))->setToDateStart();
        static::assertSame(1, $first->diffTime($last)->totalMonths);

        // Reset, for posterity.
        date_default_timezone_set($tz_default);

        // When baseline is non-UTC: use verbatim clone.
        $first = (new Time('2019-02-01', new \DateTimeZone(BootstrapTest::TIMEZONE)))->setToDateStart();
        $last = (new Time('2019-03-01', new \DateTimeZone('UTC')))->setToDateStart();
        static::assertSame(1, $first->diffTime($last)->totalMonths);

        // When deviant is non-UTC (and base is UTC), move deviant into UTC.
        $first = (new Time('2019-02-01', new \DateTimeZone('UTC')))->setToDateStart();
        $last = (new Time('2019-03-01', new \DateTimeZone(BootstrapTest::TIMEZONE)))->setToDateStart();
        static::assertSame(0, $first->diffTime($last)->totalMonths);

        /**
         * Throws exception because the two dates don't have the same timezone,
         * and falsy arg $allowUnEqualTimezones.
         * @see \SimpleComplex\Time\Time::diffTime()
         */
        $this->expectException(\RuntimeException::class);
        static::assertSame(0, $first->diffTime($last)->totalMonths);
    }

    public static function testResolve()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $datetime_local = new \DateTime();
        $datetime_utc = $datetime_local->setTimezone(new \DateTimeZone('UTC'));
        $time_local = Time::createFromDateTime($datetime_local);
        $time_utc = Time::createFromDateTime($datetime_utc);

//        \SimpleComplex\Inspect\Inspect::getInstance()->variable([
//            'time local' => Time::resolve($time_local),
//            'time utc, keep foreign' => Time::resolve($time_utc, true),
//            'time utc' => Time::resolve($time_utc),
//            'datetime local' => Time::resolve($datetime_local),
//            'datetime utc, keep foreign' => Time::resolve($datetime_utc, true),
//            'datetime utc' => Time::resolve($datetime_utc),
//        ])->log();

        $t = Time::resolve(0);
        static::assertInstanceOf(Time::class, $t);
        static::assertSame('1970-01-01T00:00:00.000000Z', $t->toISOUTC());

        $t = Time::resolve(0, true);
        static::assertInstanceOf(Time::class, $t);
        static::assertSame('1970-01-01T00:00:00.000000Z', $t->toISOUTC());

        $t = Time::resolve(-1);
        static::assertInstanceOf(Time::class, $t);
        static::assertSame('1969-12-31T23:59:59.000000Z', $t->toISOUTC());

        $t = Time::resolve($datetime_utc);
        static::assertInstanceOf(Time::class, $t);
        static::assertSame($time_local->toISOUTC(), $t->toISOUTC());
        static::assertTrue($t->timezoneIsLocal());

        // Bad URL encoding; + transformed to space.
        $t = Time::resolve('2019-04-05T10:14:47 02:00');
        static::assertInstanceOf(Time::class, $t);
        static::assertSame('2019-04-05T10:14:47.000000+02:00', $t->toISOZonal());
    }


    public function testUnixEpoch()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);


        // \DateTime::getTimestamp() must return negative when < 1970-01-01.

        $time = new Time('1900-01-01');
        $timestamp = $time->getTimestamp();
        static::assertIsInt($timestamp, '(Date < 1970-01-01)->getTimestamp() is int');
        static::assertTrue($timestamp < 0, '(Date < 1970-01-01)->getTimestamp() is negative');


        // \DateTime::getTimestamp() truncates/ignores effectively floors microseconds.

        $floor = new Time('2020-01-01T12:00:00.399999Z');
        static::assertTrue($floor->getTimestamp() % 2 == 0, 'getTimestamp() floors microseconds');
        static::assertSame(1577880000, $floor->getTimestamp(), 'floorable truncated');

        $ceil = new Time('2020-01-01T12:00:00.899999Z');
        static::assertTrue($ceil->getTimestamp() % 2 == 0, 'getTimestamp() floors microseconds');
        static::assertSame(1577880000, $ceil->getTimestamp(), 'ceilable truncated');

        static::assertSame(1577880000, $floor->unixSeconds, 'floorable floored');
        static::assertSame(1577880001, $ceil->unixSeconds, 'ceilable ceiled');


        // Milli/microseconds methods aren't exact, because floats.

        static::assertTrue(abs(1577880000400 - $floor->unixMilliseconds) < 10, 'floorable milliseconds');
        static::assertTrue(abs(1577880000900 - $ceil->unixMilliseconds) < 10, 'ceilable milliseconds');

        static::assertTrue(abs(1577880000400000 - $floor->unixMicroseconds) < 10, 'floorable microseconds');
        static::assertTrue(abs(1577880000900000 - $ceil->unixMicroseconds) < 10, 'ceilable microseconds');
    }


    public function testImmutable()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $immutable = new TimeImmutable();
        static::assertInstanceOf(TimeImmutable::class, $immutable);

        $mutated = $immutable->modifyDate(1);
        static::assertInstanceOf(TimeImmutable::class, $mutated);

        static::assertSame(12, $immutable->diffTime($mutated)->totalMonths);
    }

    public function testSubSecondPrecision()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $time = new Time('2020-01-01 00:00:00.001002');

        static::assertSame('"2020-01-01T00:00:00.001+01:00"', json_encode($time), 'json subsecond precision');

        static::assertSame('2020-01-01T00:00:00.001002+01:00', $time->toISOZonal(), 'iso zonal subsecond default');
        static::assertSame('2020-01-01T00:00:00.001002+01:00', $time->toISOZonal('micro'), 'iso zonal subsecond micro');
        static::assertSame('2020-01-01T00:00:00.001+01:00', $time->toISOZonal('milli'), 'iso zonal subsecond milli');
        static::assertSame('2020-01-01T00:00:00+01:00', $time->toISOZonal('none'), 'iso zonal subsecond none');

        static::assertSame('2019-12-31T23:00:00.001002Z', $time->toISOUTC(), 'iso utc subsecond default');
        static::assertSame('2019-12-31T23:00:00.001002Z', $time->toISOUTC('micro'), 'iso utc subsecond micro');
        static::assertSame('2019-12-31T23:00:00.001Z', $time->toISOUTC('milli'), 'iso utc subsecond milli');
        static::assertSame('2019-12-31T23:00:00Z', $time->toISOUTC('none'), 'iso utc subsecond none');
    }

    /**
     * Test interval between two literal dates, which is one hour shorter
     * if in local timezone than if in UTC timezone.
     *
     * Reason: the latter date is within +1 hour summer time, and a summer time
     * offset doesn't make time 'longer'.
     *
     * @throws \Throwable
     */
    public function testTimeIntervalUTCVersusLocal()
    {
        $signs = [
            'positive' => 1,
            'negative' => -1,
        ];

        $zones = [
            'UTC' => 'UTC',
            'local' => BootstrapTest::TIMEZONE,
        ];

        foreach ($signs as $sign_alias => $sign) {
            foreach ($zones as $zone_alias => $zone) {
                date_default_timezone_set($zone);

                $first = new Time('2000-01-01 00:00:00');
                $last = new Time('2020-06-15 12:37:59.555555');

                if ($sign == 1) {
                    $diff = $first->diffTime($last);
                } else {
                    $diff = $last->diffTime($first);
                }
                //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log();
                /*
                .  y: (integer) 20
                .  m: (integer) 5
                .  d: (integer) 14
                .  h: (integer) 12
                .  i: (integer) 59
                .  s: (integer) 59
                .  f: (float) 0.555555
                .  invert: (integer) 1
                .  days: (integer) 7471
                .  totalYears: (integer) -20
                .  totalMonths: (integer) -245
                .  totalDays: (integer) -7471
                .  totalHours: (integer) -179316
                .  totalMinutes: (integer) -10758997
                .  totalSeconds: (integer) -645539879
                 */
                static::assertSame(20, $diff->y, $sign_alias . ' (' . $zone_alias . ') y');
                static::assertSame(5, $diff->m, $sign_alias . ' (' . $zone_alias . ') m');
                static::assertSame(14, $diff->d, $sign_alias . ' (' . $zone_alias . ') d');
                if ($zone_alias == 'UTC') {
                    static::assertSame(12, $diff->h, $sign_alias . ' (' . $zone_alias . ') h');
                } else {
                    static::assertSame(11, $diff->h, $sign_alias . ' (' . $zone_alias . ') h');
                }
                static::assertSame(37, $diff->i, $sign_alias . ' (' . $zone_alias . ') i');
                static::assertSame(59, $diff->s, $sign_alias . ' (' . $zone_alias . ') s');
                static::assertSame(0.555555, $diff->f, $sign_alias . ' (' . $zone_alias . ') f');
                static::assertSame(7471, $diff->days, $sign_alias . ' (' . $zone_alias . ') days');
                static::assertSame($sign * 20, $diff->totalYears, $sign_alias . ' (' . $zone_alias . ') totalYears');
                static::assertSame($sign * 245, $diff->totalMonths, $sign_alias . ' (' . $zone_alias . ') totalMonths');
                static::assertSame($sign * 7471, $diff->totalDays, $sign_alias . ' (' . $zone_alias . ') totalDays');
                if ($zone_alias == 'UTC') {
                    static::assertSame($sign * 179316, $diff->totalHours, $sign_alias . ' (' . $zone_alias . ') totalHours');
                    static::assertSame($sign * 10758997, $diff->totalMinutes, $sign_alias . ' (' . $zone_alias . ') totalMinutes');
                    static::assertSame($sign * 645539879, $diff->totalSeconds, $sign_alias . ' (' . $zone_alias . ') totalSeconds');
                    static::assertSame(
                        $sign * 645539879,
                        $sign * ($last->getTimestamp() - $first->getTimestamp()),
                        $sign_alias . ' (' . $zone_alias . ') DateTime::getTimestamp()'
                    );
                } else {
                    static::assertSame($sign * 179315, $diff->totalHours, $sign_alias . ' (' . $zone_alias . ') totalHours');
                    static::assertSame($sign * 10758937, $diff->totalMinutes, $sign_alias . ' (' . $zone_alias . ') totalMinutes');
                    static::assertSame($sign * 645536279, $diff->totalSeconds, $sign_alias . ' (' . $zone_alias . ') totalSeconds');
                    static::assertSame(
                        $sign * 645536279,
                        $sign * ($last->getTimestamp() - $first->getTimestamp()),
                        $sign_alias . ' (' . $zone_alias . ') DateTime::getTimestamp()'
                    );
                }
            }
        }
    }
}
