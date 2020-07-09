## (PHP) Time ##
<small>composer namespace: simplecomplex/**time**</small>

- [Requirements](#Requirements)
- [License](#MIT-licensed)

**[Time](src/Time.php)** extends native \DateTime to fix shortcomings and defects,  
and provide more, simpler and safer getters and setters.

Features:
 * enhanced timezone awareness
 * diff - diffTime() - works correctly with non-UTC timezones
 * safer formatting and modifying
 * is stringable (sic!), to ISO-8601
 * JSON serializes to string ISO-8601 with timezone marker
 * freezable
 * more and simpler getters and setters
 
Inspired by Javascript's Date class, and secures better Javascript interoperability  
by stresssing and facilitating timezone awareness, and by JSON serializing to ISO-8601 timestamp string.

#### Related classes ####

**[TimeInterval](src/TimeInterval.php)** is a mock \DateInterval, returned by Time::diffTime().  
Has the same properties as \DateInterval plus **signed relatives and totals**, and works with non-UTC timezones.

**[TimeSpan](src/TimeSpan.php)**'s main feature is to check **overlap** vs. another TimeSpan.

**[TimeImmutable](src/TimeImmutable.php)** is an immutable extending Time, not \DateTimeImmutable.

**[TimeLocal](src/TimeLocal.php)** forces new instance to be in local (default) timezone.


#### Other info ####

The Time package was originally forked from [simplecomplex/utils](https://github.com/simplecomplex/php-utils)' time classes.

### Requirements ###

- PHP >=7.2 (64-bit)

#### Development requirements ####
- [PHPUnit](https://github.com/sebastianbergmann/phpunit) ^8
- [Jasny PHPUnit extension](https://github.com/jasny/phpunit-extension) ^0.2

#### Suggestions ####
- [SimpleComplex Inspect](https://github.com/simplecomplex/inspect)

### MIT licensed ###

[License and copyright](https://github.com/simplecomplex/php-time/blob/master/LICENSE).
[Explained](https://tldrlegal.com/license/mit-license).
