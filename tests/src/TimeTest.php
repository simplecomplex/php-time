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
use SimpleComplex\Time\TimeLocal;
use SimpleComplex\Time\TimeImmutable;
use SimpleComplex\Time\TimeSpan;

/**
 * @code
 * // CLI, in document root:
 * backend/vendor/bin/phpunit --do-not-cache-result backend/vendor/simplecomplex/time/tests/src/TimeTest.php
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


    public function testDateTimeArgumentTypeHinting()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        /**
         * Fails if Time::() type-hints it's parameter as \DateInterval.
         * @see Time::add()
         */
        $time = new Time();
        $clone = clone $time;
        static::assertTrue($clone->epochMicro === $time->epochMicro);
    }

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

        /** @var Time[] $times */
        $times = [
            'Time' => new Time('2020-06-15 12:37:59.555555'),
            'TimeImmutable' => new TimeImmutable('2020-06-15 12:37:59.555555'),
        ];

        foreach ($times as $class => $time) {
            static::assertIsInt($time->year, '(' . $class . ')->year integer');
            static::assertSame(2020, $time->year, '(' . $class . ')->year value');
            static::assertSame(6, $time->month, '(' . $class . ')->month value');
            static::assertSame(15, $time->date, '(' . $class . ')->date value');
            static::assertSame(12, $time->hour, '(' . $class . ')->hour value');
            static::assertSame(37, $time->minute, '(' . $class . ')->minute value');
            static::assertSame(59, $time->second, '(' . $class . ')->second value');
            static::assertSame(555, $time->milli, '(' . $class . ')->milli value');
            static::assertSame(555555, $time->micro, '(' . $class . ')->micro value');
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
            static::assertSame(12, $time->{'getHours'}(), '(' . $class . ')->hour value');
            $this->expectDeprecation();
            static::assertSame(37, $time->{'getMinutes'}(), '(' . $class . ')->minute value');
            $this->expectDeprecation();
            static::assertSame(59, $time->{'getSeconds'}(), '(' . $class . ')->second value');
            $this->expectDeprecation();
            static::assertSame(555, $time->{'getMilliseconds'}(), '(' . $class . ')->milli value');
            $this->expectDeprecation();
            static::assertSame(555555, $time->{'getMicroseconds'}(), '(' . $class . ')->micro value');
        }
    }

    /**
     * @see \SimpleComplex\Time\Time::modifyDate()
     *
     * @throws \Exception
     */
    public function testModifyDate()
    {
        $time = new Time('2018-01-01');

        $years = $months = $days = 1;
        static::assertSame('2018-01-01', (clone $time)->modifyDate(0, 0)->ISODate);
        static::assertSame('2019-02-02', (clone $time)->modifyDate($years, $months, $days)->ISODate);
        // 2017-01-01
        // 2016-12-01
        // 2016-11-30
        static::assertSame('2016-11-30', (clone $time)->modifyDate(-$years, -$months, -$days)->ISODate);

        // Modifying month only.------------------------------------------------
        $log = [];

        $year = 2018;
        $month = 1;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = 25;
        $log[] = '';
        $log[] = '     ' . $time->ISODate;
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
                $result = (clone $time)->modifyDate(0, $months)->ISODate
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 12;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = -25;
        $log[] = '';
        $log[] = '     ' . $time->ISODate;
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
                $result = (clone $time)->modifyDate(0, $months)->ISODate
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 7;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = 25;
        $log[] = '';
        $log[] = '     ' . $time->ISODate;
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
                $result = (clone $time)->modifyDate(0, $months)->ISODate
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }

        $year = 2018;
        $month = 7;
        $day = 1;
        $time = (new Time())->setDate($year, $month, $day);
        $limit = -25;
        $log[] = '';
        $log[] = '     ' . $time->ISODate;
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
                $result = (clone $time)->modifyDate(0, $months)->ISODate
            );
            $log[] = str_pad('' . $months, 3, ' ', STR_PAD_LEFT) . ': ' . $result;
        }


        // /Modifying month only.-----------------------------------------------

        // Days only.
        $time = new Time('2018-01-01');
        static::assertSame('2018-01-02', (clone $time)->modifyDate(0, 0, 1)->ISODate);

        // Last day of February.
        $time = new Time('2018-01-31');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, 1)->ISODate);
        // Leap year last day of February.
        static::assertSame('2020-02-29', (clone $time)->modifyDate(2, 1)->ISODate);

        // Last day of February.
        $time = new Time('2018-01-01');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, 1)->setToLastDayOfMonth()->ISODate);
        $time = new Time('2018-03-31');
        static::assertSame('2018-02-28', (clone $time)->modifyDate(0, -1)->ISODate);


        $time = new Time('2018-01-01');
        static::assertSame('2018-02-20', (clone $time)->modifyDate(0, 0, 50)->ISODate);
    }

    /**
     * @see \SimpleComplex\Time\Time::modifyTime()
     *
     * @throws \Exception
     */
    public function testModifyTime()
    {
        $time = new Time('2018-01-01 15:37:13');
        static::assertSame('2018-01-01 16:38:14', (clone $time)->modifyTime(1, 1, 1)->ISODateTime);
        static::assertSame('2018-01-02 16:38:14', (clone $time)->modifyTime(25, 1, 1)->ISODateTime);
        static::assertSame('2017-12-31 14:36:12', (clone $time)->modifyTime(-25, -1, -1)->ISODateTime);
    }

    /**
     * @see \SimpleComplex\Time\Time::cloneCorrectTimezone()
     *
     * @throws \Exception
     */
    public function testCloneCorrectTimezone()
    {
        $tz_utc = new \DateTimeZone('UTC');
        // Hardcoded timezone, because times of change
        // are probably tied to the timezone.
        $tz_local = new \DateTimeZone('Europe/Copenhagen');

        $timestamps = [
            'winter' => [
                '2018-12-31 12:00:00' => 1,
                '2019-01-01 00:00:00' => 1,
                '2019-01-01 12:00:00' => 1,
            ],
            'winterToSummer' => [
                '2019-03-30 12:00:00' => 1,
                '2019-03-31 01:59:59' => 1,
                // Europe/Copenhagen: 2019-03-31 02:00:00.
                '2019-03-31 03:00:00' => 2,
                '2019-03-31 12:00:00' => 2,
            ],
            'summer' => [
                '2019-06-30 12:00:00' => 2,
                '2019-07-01 00:00:00' => 2,
                '2019-07-01 12:00:00' => 2,
            ],
            'summerToWinter' => [
                '2019-10-26 12:00:00' => 2,
                '2019-10-27 01:59:59' => 2,
                // Europe/Copenhagen: 2019-10-27 03:00:00.
                '2019-10-27 02:00:00' => 1,
                '2019-10-27 12:00:00' => 1,
            ],
        ];

        $classes = [
            Time::class,
            TimeLocal::class,
            TimeImmutable::class
        ];

        foreach ($classes as $class) {
            foreach ($timestamps as $point => $list) {
                $index = -1;
                foreach ($list as $timestamp => $diff) {
                    ++$index;
                    $utc = new Time($timestamp, $tz_utc);
                    $local = (clone $utc)->setTimezone($tz_local);

                    // Something thought that it was in local timezone.
                    /** @var Time $wrong */
                    $wrong = new $class($timestamp, $tz_local);
                    // But we know better.
                    $moved = $wrong->cloneCorrectTimezone($tz_utc, $tz_local);

                    static::assertSame(
                        $diff * -3600,
                        $wrong->getTimestamp() - $utc->getTimestamp(),
                        '(' . $class . ')' . $point . '[' . $timestamp . '] wrong vs. UTC'
                    );
                    static::assertSame(
                        $diff * -3600,
                        $wrong->getTimestamp() - $local->getTimestamp(),
                        '(' . $class . ')' . $point . '[' . $timestamp . '] wrong vs. local'
                    );

                    static::assertSame(
                        0,
                        $moved->getTimestamp() - $utc->getTimestamp(),
                        '(' . $class . ')' . $point . '[' . $timestamp . '] moved vs. UTC'
                    );
                    static::assertSame(
                        0,
                        $moved->getTimestamp() - $local->getTimestamp(),
                        '(' . $class . ')' . $point . '[' . $timestamp . '] moved vs. local'
                    );
                }
            }
        }
    }


    /**
     * @throws \Exception
     *
     * @see \SimpleComplex\Time\Time::diffTime()
     * @see \SimpleComplex\Time\Time::diffDstIgnorant()
     */
    public function testDiffTime()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $first = (new Time('2019-02-01'))->setToDateStart();
        $last = (new Time('2019-03-01'))->setToDateStart();

//        // Native \DateInterval's public properties are writable,
//        // but with no effect.
//        $interval_mutable = $first->diff($last);
//        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($interval_mutable)->log();
//        static::assertSame(0, $interval_mutable->h, '');
//        $interval_mutable->h = 2;
//        static::assertSame(2, $interval_mutable->h, '');
//
//        // Our interval class properties are read-only.
//        $interval_constant = $first->diffTime($last);
//        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($interval_constant)->log();
//        static::assertSame(0, $interval_constant->h, '');
//        $this->expectException(\RuntimeException::class);
//        /** @noinspection Annotator */
//        $interval_constant->{'h'} = 2;

        /**
         * \SimpleComplex\Time\Time::diffTime()
         *
         * Fixes that native diff()|\DateInterval calculation doesn't work correctly
         * with other timezone than UTC.
         * @see https://bugs.php.net/bug.php?id=52480
         */

        $tz_default = date_default_timezone_get();

        // Summertime: before, across shift, after shift.
        $scenarios = [
            'before' => ['2019-02-01', '2019-03-01'],
            'across' => ['2019-03-01', '2019-04-01'],
            'after' => ['2019-04-01', '2019-05-01'],
        ];

        foreach ($scenarios as $name => $dates) {
            // UTC.
            date_default_timezone_set('UTC');
            $first = (new Time($dates[0]))->setToDateStart();
            $last = (new Time($dates[1]))->setToDateStart();
            $actual = $first->diffTime($last);
            static::assertInstanceOf(\SimpleComplex\Time\TimeIntervalUnified::class, $actual);
            $dstIgnorant = $first->diffDstIgnorant($last);
            static::assertInstanceOf(\SimpleComplex\Time\TimeIntervalUnified::class, $dstIgnorant);
            //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log();
            static::assertSame(1, $actual->totalMonths, $name);
            static::assertSame(1, $dstIgnorant->totalMonths, $name);
            $days = $first->monthLengthDays();
            static::assertSame($days, $actual->totalDays, $name);
            static::assertSame($days, $dstIgnorant->totalDays, $name);

            static::assertSame(0, $actual->relativeHours, $name);
            static::assertSame(0, $dstIgnorant->relativeHours, $name);

            // Native diff() gets it wrong here, because non-UTC.
            date_default_timezone_set(BootstrapTest::TIMEZONE);
            $first = (new Time($dates[0]))->setToDateStart();
            $last = (new Time($dates[1]))->setToDateStart();
            $diff = $first->toDateTime()->diff($last->toDateTime());
            //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log();
            $months = $name == 'across' ? 1 : 0;
            static::assertSame($months, $diff->m, $name);

            // Local.
            date_default_timezone_set(BootstrapTest::TIMEZONE);
            $first = (new Time($dates[0]))->setToDateStart();
            $last = (new Time($dates[1]))->setToDateStart();
            $actual = $first->diffTime($last);
            static::assertNotInstanceOf(\SimpleComplex\Time\TimeIntervalUnified::class, $actual);
            static::assertInstanceOf(\SimpleComplex\Time\TimeInterval::class, $actual);
            $dstIgnorant = $first->diffDstIgnorant($last);
            static::assertNotInstanceOf(\SimpleComplex\Time\TimeIntervalUnified::class, $dstIgnorant);
            static::assertInstanceOf(\SimpleComplex\Time\TimeIntervalDstIgnorant::class, $dstIgnorant);
            //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log();
            static::assertSame(1, $actual->totalMonths, $name);
            static::assertSame(1, $dstIgnorant->totalMonths, $name);
            $days = $first->monthLengthDays();
            static::assertSame($days - ($name == 'across' ? 1 : 0), $actual->totalDays, $name);
            static::assertSame($days, $dstIgnorant->totalDays, $name);

            static::assertSame($name == 'across' ? 23 : 0, $actual->relativeHours, $name);
            static::assertSame(0, $dstIgnorant->relativeHours, $name);
        }

        // Reset, for posterity.
        date_default_timezone_set($tz_default);

        // Different timezones.
        $first = (new Time('2019-02-01', new \DateTimeZone(BootstrapTest::TIMEZONE)))->setToDateStart();
        $last = (new Time('2019-03-01', new \DateTimeZone('UTC')))->setToDateStart();
        //$first = (new Time('2019-02-01', new \DateTimeZone('UTC')))->setToDateStart();
        //$last = (new Time('2019-03-01', new \DateTimeZone(BootstrapTest::TIMEZONE)))->setToDateStart();
        /**
         * Throws exception because the two dates don't have the same timezone,
         * and falsy arg $allowUnEqualTimezones.
         * @see \SimpleComplex\Time\Time::diffTime()
         */
        $this->expectException(\RuntimeException::class);
        static::assertSame(0, $first->diffTime($last)->totalMonths);
    }

    /**
     * @throws \Exception
     */
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

        static::assertSame(1577880000, $floor->epochSecond, 'floorable floored');
        static::assertSame(1577880000, $ceil->epochSecond, 'ceilable ceiled');


        // Milli/microseconds methods aren't exact, because floats.

        static::assertTrue(abs(1577880000400 - $floor->epochMilli) < 10, 'floorable milliseconds');
        static::assertTrue(abs(1577880000900 - $ceil->epochMilli) < 10, 'ceilable milliseconds');

        static::assertTrue(abs(1577880000400000 - $floor->epochMicro) < 10, 'floorable microseconds');
        static::assertTrue(abs(1577880000900000 - $ceil->epochMicro) < 10, 'ceilable microseconds');
    }

    /**
     * @throws \Exception
     */
    public function testImmutable()
    {
        date_default_timezone_set(BootstrapTest::TIMEZONE);

        $immutable = new TimeImmutable();
        static::assertInstanceOf(TimeImmutable::class, $immutable);

        $mutated = $immutable->modifyDate(1);
        static::assertInstanceOf(TimeImmutable::class, $mutated);

        static::assertSame(12, $immutable->diffTime($mutated)->totalMonths);
    }

    /**
     * @throws \Exception
     */
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
     * @throws \Exception
     */
    public function testTimeIntervalUTCVersusLocalMidday()
    {
        $signs = [
            'positive' => 1,
            'negative' => -1,
        ];

        $zones = [
            'UTC' => 'UTC',
            'local' => BootstrapTest::TIMEZONE,
        ];

        foreach ($signs as $sign => $sg) {
            foreach ($zones as $zone => $zn) {
                date_default_timezone_set($zn);

                $first = new Time('2000-01-01 00:00:00');
                $last = new Time('2020-06-15 12:37:59.555555');

                if ($sg == 1) {
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
                static::assertSame(20, $diff->y, $sign . ' (' . $zone . ') y');
                static::assertSame(5, $diff->m, $sign . ' (' . $zone . ') m');
                static::assertSame(14, $diff->d, $sign . ' (' . $zone . ') d');
                static::assertSame($zn == 'UTC' ? 12 : 11, $diff->h, $sign . ' (' . $zone . ') h');
                static::assertSame(37, $diff->i, $sign . ' (' . $zone . ') i');
                static::assertSame(59, $diff->s, $sign . ' (' . $zone . ') s');
                static::assertSame(0.555555, $diff->f, $sign . ' (' . $zone . ') f');
                // Same number of days.
                static::assertSame(7471, $diff->days, $sign . ' (' . $zone . ') days');
                static::assertSame($sg * 20, $diff->totalYears, $sign . ' (' . $zone . ') totalYears');
                static::assertSame($sg * 245, $diff->totalMonths, $sign . ' (' . $zone . ') totalMonths');
                static::assertSame($sg * 7471, $diff->totalDays, $sign . ' (' . $zone . ') totalDays');
                static::assertSame($sg * ($zn == 'UTC' ? 179316 : 179315), $diff->totalHours, $sign . ' (' . $zone . ') totalHours');
                static::assertSame($sg * ($zn == 'UTC' ? 10758997 : 10758937), $diff->totalMinutes, $sign . ' (' . $zone . ') totalMinutes');
                static::assertSame($sg * ($zn == 'UTC' ? 645539879 : 645536279), $diff->totalSeconds, $sign . ' (' . $zone . ') totalSeconds');
                static::assertSame(
                    $sg * ($zn == 'UTC' ? 645539879 : 645536279),
                    $sg * ($last->getTimestamp() - $first->getTimestamp()),
                    $sign . ' (' . $zone . ') DateTime::getTimestamp()'
                );

                if ($zone == 'local') {
                    if ($sign == 'positive') {
                        static::assertSame('P20Y5M14DT11H37M59S', $diff->ISODuration);
//                        \SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log(
//                            'debug',
//                            $first->toISOZonal() . ' >< ' . $last->toISOZonal() . ":\n" . $diff->format(
//                                '%Y years, %m months, %d days - %H hours, %i minutes, %s seconds'
//                            )
//                        );

                        //static::assertInstanceOf(\DateInterval::class, $diff->toDateInterval());
                        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff->toDateInterval())->log();
                    }
                    else {
                        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff->toDateInterval())->log();
                    }
                }
            }
        }
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
    public function testTimeIntervalUTCVersusLocalMidnight()
    {
        $signs = [
            'positive' => 1,
            'negative' => -1,
        ];

        $zones = [
            'UTC' => 'UTC',
            'local' => BootstrapTest::TIMEZONE,
        ];

        foreach ($signs as $sign => $sg) {
            foreach ($zones as $zone => $zn) {
                date_default_timezone_set($zn);

                $first = new Time('2000-01-01 00:00:00.000000');
                $last = new Time('2020-06-15 00:00:00.000000');

                if ($sg == 1) {
                    $diff = $first->diffTime($last);
                } else {
                    $diff = $last->diffTime($first);
                }
                //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log();
                /*
                .  y: (integer) 20
                .  m: (integer) 5
                .  d: (integer) 14
                .  h: (integer) 0
                .  i: (integer) 0
                .  s: (integer) 0
                .  f: (float) 0.000000
                .  invert: (integer) 1
                .  days: (integer) 7471
                .  totalYears: (integer) -20
                .  totalMonths: (integer) -245
                .  totalDays: (integer) -7471
                .  totalHours: (integer) -179316
                .  totalMinutes: (integer) -10758997
                .  totalSeconds: (integer) -645539879
                 */
                static::assertSame(20, $diff->y, $sign . ' (' . $zone . ') y');
                static::assertSame(5, $diff->m, $sign . ' (' . $zone . ') m');
                static::assertSame(14, $diff->d, $sign . ' (' . $zone . ') d');
                static::assertSame($zn == 'UTC' ? 0 : 23, $diff->h, $sign . ' (' . $zone . ') h');
                static::assertSame(0, $diff->i, $sign . ' (' . $zone . ') i');
                static::assertSame(0, $diff->s, $sign . ' (' . $zone . ') s');
                static::assertSame(0.0, $diff->f, $sign . ' (' . $zone . ') f');
                // Differing number of days.
                static::assertSame($zn == 'UTC' ? 7471 : 7470, $diff->days, $sign . ' (' . $zone . ') days');
                static::assertSame($sg * 20, $diff->totalYears, $sign . ' (' . $zone . ') totalYears');
                static::assertSame($sg * 245, $diff->totalMonths, $sign . ' (' . $zone . ') totalMonths');
                static::assertSame($sg * ($zn == 'UTC' ? 7471 : 7470), $diff->totalDays, $sign . ' (' . $zone . ') totalDays');
                static::assertSame($sg * ($zn == 'UTC' ? 179304 : 179303), $diff->totalHours, $sign . ' (' . $zone . ') totalHours');
                static::assertSame($sg * ($zn == 'UTC' ? 10758240 : 10758180), $diff->totalMinutes, $sign . ' (' . $zone . ') totalMinutes');
                static::assertSame($sg * ($zn == 'UTC' ? 645494400 : 645490800), $diff->totalSeconds, $sign . ' (' . $zone . ') totalSeconds');
                static::assertSame(
                    $sg * ($zn == 'UTC' ? 645494400 : 645490800),
                    $sg * ($last->getTimestamp() - $first->getTimestamp()),
                    $sign . ' (' . $zone . ') DateTime::getTimestamp()'
                );

                if ($zone == 'local') {
                    if ($sign == 'positive') {
                        static::assertSame('P20Y5M14DT23H0M0S', $diff->ISODuration);
//                        \SimpleComplex\Inspect\Inspect::getInstance()->variable($diff)->log(
//                            'debug',
//                            $first->toISOZonal() . ' >< ' . $last->toISOZonal() . ":\n" . $diff->format(
//                                '%Y years, %m months, %d days - %H hours, %i minutes, %s seconds'
//                            )
//                        );

                        //static::assertInstanceOf(\DateInterval::class, $diff->toDateInterval());
                        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff->toDateInterval())->log();
                    }
                    else {
                        //\SimpleComplex\Inspect\Inspect::getInstance()->variable($diff->toDateInterval())->log();
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function testTimeSpanOverlap()
    {
        $baseline_from = new Time('2019-01-01');
        $baseline_to = new Time('2019-01-30');
        $baseline = new TimeSpan($baseline_from, $baseline_to);

        $subject = new TimeSpan(new Time('2019-01-31'), new Time('2019-02-04'));
        static::assertSame(TimeSpan::OVERLAP_NONE, $baseline->overlap($subject));

        $subject = new TimeSpan(new Time('2019-01-01'), new Time('2019-01-30'));
        static::assertSame(TimeSpan::OVERLAP_IDENTITY, $baseline->overlap($subject));

        $subject = new TimeSpan(new Time('2018-12-31'), new Time('2019-01-31'));
        static::assertSame(TimeSpan::OVERLAP_ENCLOSES, $baseline->overlap($subject));

        $subject = new TimeSpan(new Time('2019-01-02'), new Time('2019-01-29'));
        static::assertSame(TimeSpan::OVERLAP_IS_SUBSET, $baseline->overlap($subject));

        $subject = new TimeSpan(new Time('2018-12-31'), new Time('2019-01-01'));
        static::assertSame(TimeSpan::OVERLAP_ENDS_WITHIN, $baseline->overlap($subject));

        $subject = new TimeSpan(new Time('2019-01-30'), new Time('2019-01-31'));
        static::assertSame(TimeSpan::OVERLAP_BEGINS_WITHIN, $baseline->overlap($subject));
    }
}
