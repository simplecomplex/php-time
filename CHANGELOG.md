# Changelog

All notable changes to **simplecomplex/time** will be documented in this file,
using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.


## [Unreleased]

### Added
* Forked from simplecomplex/utils time classes.
* diffDate() works correctly with non-UTC timezones, returns \DateInterval.
* Getters toUnixSeconds|toUnixMilliseconds|toUnixMicroseconds(), which contrary
to \DateTime.getTimestamp() rounds microseconds; instead of floor'ing.

### Changed
* Requires PHP >=7.2; not 7.0.
* require-dev phpunit ^8; not ^6.5.
* resolve() check for DateTimeInterface, not just DateTime.
* TimeInterval now implements Explorable\ExplorableInterface instead of
extending Utils\Explorable.
* TimeInterval renamed; from TimeIntervalConstant (daft: \DateInterval is
effectively constant, by itself).
* diffTime() replaces diffConstant(), the latter now deprecated.
* TimeInterval::getDateInterval() replaces getMutable(); now deprecated.
* Changelog in standard keepachangelog format; previous was idiosyncratic.

### Fixed
* diffDate() must move non-UTC timezone correctly, not use literal time in other
(UTC) timezone.
* diffDate() must NOT use offset as indication of timezone similarity.
* diffDate() shan't care whether this and subject's timezones are same/similar;
parameter $allowUnEqualTimezones removed.


## [0.1] - 2018-05-23

### Added
* Initial version, as part of [SimpleComplex Utils](https://github.com/simplecomplex/php-utils).
