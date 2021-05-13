# (PHP) Time
<small>composer namespace: simplecomplex/**time**</small>

- [Requirements](#Requirements)
- [License](#MIT-licensed)

### [Time](src/Time.php)
extends native \DateTime to fix shortcomings and defects, and provide more properties, getters and setters.

## Features
- enhanced timezone awareness
- diff which works correctly with non-UTC timezones
- safer formatting and modifying
- is stringable (sic!), to ISO-8601
- JSON serializes to string ISO-8601 with timezone marker
- freezable
- directly accessible time part properties
- more, simpler and safer getters and setters

## Related classes

### [TimeInterval](src/TimeInterval.php)
has the same properties as native `\DateInterval` plus **signed relatives and totals**, and works with non-UTC timezones.

#### Time::diffExact()
returns interval with exact differences.

#### Time::diffHabitual()
returns an interval which ignores daylight saving time shift.<br>
In everyday business you often don't want the difference between a date _outside_ daylight saving time (DST)<br>
and a date _inside_ DST to be off by the DST offset.<br>
That offset can be particularly nasty if either of the dates is _at_ or _close to_ midnight,<br>
because then the _days_ difference may get 1 off.

### [TimeSpan](src/TimeSpan.php)
It's main feature is to check **overlap** vs. another TimeSpan.

### [TimeImmutable](src/TimeImmutable.php)
Is an immutable extending Time, not `\DateTimeImmutable`.

### [TimeLocal](src/TimeLocal.php)
Forces instances to be in local (default) timezone.


## Requirements

- PHP >=7.2 (64-bit)

### Development requirements
- [PHPUnit](https://github.com/sebastianbergmann/phpunit) ^8 || ^9
- [Jasny PHPUnit extension](https://github.com/jasny/phpunit-extension) ^0.2

### Suggestions
- [SimpleComplex Inspect](https://github.com/simplecomplex/inspect)

## MIT licensed

[License and copyright](https://github.com/simplecomplex/php-time/blob/master/LICENSE).
[Explained](https://tldrlegal.com/license/mit-license).
