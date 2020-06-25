## (PHP) Time ##

**``` Time ```** extends the native DateTime class to fix shortcomings and defects,  
and provide more, simpler and safer getters and setters.

Features:
 * enhanced timezone awareness
 * diff (diffDate/diffTime) works correctly with non-UTC timezones
 * safer formatting and modifying
 * is stringable (sic!), to ISO-8601
 * JSON serializes to string ISO-8601 with timezone marker
 * freezable
 * more and simpler getters and setters
 
Inspired by Javascript's Date class, and secures better Javascript interoperability  
by stresssing and facilitating timezone awareness, and by JSON serializing to ISO-8601 timestamp string;  
_not_ a phoney Javascript object representing a PHP DateTime's inner properties. 

Time is forked from simplecomplex/utils' time classes.

### Requirements ###

- PHP >=7.2

#### Development requirements ####
- [PHPUnit](https://github.com/sebastianbergmann/phpunit) ^8
- [Jasny PHPUnit extension](https://github.com/jasny/phpunit-extension) ^0.2

#### Suggestions ####
- [SimpleComplex Inspect](https://github.com/simplecomplex/inspect)
