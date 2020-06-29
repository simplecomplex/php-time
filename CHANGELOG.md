# Changelog

All notable changes to **simplecomplex/time** will be documented in this file,
using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.


## [Unreleased]

### Added
* Forked from simplecomplex/utils (v2.3) time classes.
* Properties unixSeconds|unixMilliseconds|unixMicroseconds, which contrary
to \DateTime.getTimestamp() rounds microseconds; instead of floor'ing.
* Time::setSubSecondPrecision() to set ISO/JSON precision ad-hoc across all new
  instances.
* TimeImmutable
* TimeSpan.

### Changed
* Requires PHP >=7.2; not 7.0.
* require-dev phpunit ^8; not ^6.5.
* resolve() check for DateTimeInterface, not just DateTime.
* TimeInterval now implements Explorable\ExplorableInterface instead of
extending Utils\Explorable.
* TimeInterval renamed; from TimeIntervalConstant (daft: \DateInterval is
effectively constant, by itself).
* diffTime() replaces diffConstant(), the latter now deprecated.
* TimeInterval::toDateInterval() replaces getMutable(); now deprecated.
* Properties for time parts and simple no-zone ISO patterns replace methods;
like ->month (->getMonth()) and ->dateISO (->getDateISO).
* toISOZonal|toISOUTC() default to subseconds precision micro (was none),  
and jsonSerialize() default to subseconds precision milli (was none).  
Native DateTime's microseconds support was incomplete until PHP 7.1;
around the time this library was begun.
* Time::setJsonSerializePrecision() removed; no deprecation parachute, was daft
  to set it on instance instead of class.
* Changelog in standard keepachangelog format; previous was idiosyncratic.

### Fixed
* diffTime() must use two DateIntervals when non-UTC timezone; one for years,
month, days, and another for total days, and hours, minutes, seconds.
* diffTime() must NOT use offset as indication of timezone similarity.


## [0.1] - 2018-05-23

### Added
* Initial version, as part of [SimpleComplex Utils](https://github.com/simplecomplex/php-utils).
