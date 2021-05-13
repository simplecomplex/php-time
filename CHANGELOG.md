# Changelog

All notable changes to **simplecomplex/time** will be documented in this file,
using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.


## [Unreleased]

### Added
- 'Habitual' diff regime, which ignores daylight saving time (DST).

### Changed
- Deprecated Time::diffTime(), TimeSpan::timeInterval(), TimeSpan::diffTimeSpan().
- Surely phpunit ^9 will work too.

### Fixed
- TimeImmutable::modifyDate() could only set months to zero.
- TimeImmutable::modifyTime() could only set minutes to zero.
- Don't rename child method parameter for no good reason.
- keepachangelog bullet is dash, not star.


## [1.1.0] - 2020-12-01

### Added
- Time::cloneCorrectTimezone() to handle timezone cockups.
- Time::__debugInfo, to make dumpable by other means than print_r/var_dump.


## [1.0.1] - 2020-08-07

### Fixed
- Time::createFromImmutable() cannot type-hint it's parameter, because parent
\DateTime::createFromImmutable() - available from PHP 7.3 - doesn't.
- TimeSpan::overlap() return type type-hint.


## [1.0] - 2020-07-08

### Added
- Forked from simplecomplex/utils (v2.3) time classes.
- Time::setSubSecondPrecision() to set ISO/JSON precision ad-hoc across all new
  instances.
- TimeImmutable.
- TimeSpan.

### Changed
- Requires PHP >=7.2 (not 7.0), and 64-bit.
- Doesn't depend on any other packages.
- require-dev phpunit ^8; not ^6.5.
- resolve() check for DateTimeInterface, not just DateTime.
- TimeInterval no longer extends Utils\Explorable.
- TimeInterval renamed; from TimeIntervalConstant.
- diffTime() replaces diffConstant(), the latter now deprecated.
- TimeInterval::toDateInterval() replaces getMutable(); now deprecated.
- Properties for time parts and simple no-zone ISO patterns replace methods;
like ->month (->getMonth()) and ->ISODate (->getDateISO).
- toISOZonal|toISOUTC() default to subseconds precision micro (was none),  
and jsonSerialize() default to subseconds precision milli (was none).  
Native DateTime's microseconds support was incomplete until PHP 7.1;
around the time this library was begun.
- Time::setJsonSerializePrecision() removed; no deprecation parachute, was daft
  to set it on instance instead of class.
- Changelog in standard keepachangelog format; previous was idiosyncratic.

### Fixed
- diffTime() must use two DateIntervals when non-UTC timezone; one for years,
month, days, and another for total days, and hours, minutes, seconds.
- diffTime() must NOT use offset as indication of timezone similarity.


## [0.1] - 2018-05-23

### Added
- Initial version, as part of [SimpleComplex Utils](https://github.com/simplecomplex/php-utils).
