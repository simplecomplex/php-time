## (PHP) Time ##

**``` Time ```** extends the native DateTime class to fix shortcomings and defects,  
and provide more, simpler and safer getters and setters.

Features:
 * is stringable (sic!), to ISO-8601
 * JSON serializes to string ISO-8601 with timezone marker
 * freezable
 * enhanced timezone awareness
 * diff (diffConstant, that is) works correctly across differing timezones
 * simpler and safer getters and setters
 
It's inspired by Javascript's Date class, and secures better Javascript interoperability  
by stresssing and facilitating timezone awareness,  and by JSON serializing to ISO-8601 timestamp string;  
_not_ a phoney Javascript object representing a PHP DateTime's inner properties. 

Time is a fork of simplecomplex/utils' time classes. This Time has no dependencies.

### Requirements ###

- PHP >=7.2

#### Development requirements ####
- [PHPUnit](https://github.com/sebastianbergmann/phpunit)

#### Suggestions ####
- [SimpleComplex Inspect](https://github.com/simplecomplex/inspect)
