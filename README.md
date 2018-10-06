[![Build Status](https://travis-ci.org/yswery/PHP-DNS-SERVER.svg?branch=version-1)](https://travis-ci.org/yswery/PHP-DNS-SERVER?branch=version-1)
[![Coverage Status](https://coveralls.io/repos/yswery/PHP-DNS-SERVER/badge.png?branch=version-1)](https://coveralls.io/github/yswery/PHP-DNS-SERVER?branch=version-1)


# PHP DNS Server

This is an Authoritative DNS Server written in pure PHP.
It will listen to DNS request on the default port (Default: port 53) and give answers about any domain that it has DNS records for.
This class can be used to give DNS responses dynamically based on your pre-existing PHP code.

## Requirements

* `PHP 7.1+`
* Needs either `sockets` or `socket_create` PHP extension loaded (which they are by default)

## Example

Here is an example of DNS server usage:
```php
require_once __DIR__.'/../vendor/autoload.php';

// JsonResolver created and provided with path to file with json dns records
$jsonResolver = new yswery\DNS\Resolver\JsonResolver([
    '/path/to/zones/example.com.json',
    '/path/to/zone/test.com.json',
]);

// System resolver acting as a fallback to the JsonResolver
$systemResolver = new yswery\DNS\Resolver\SystemResolver();

// StackableResolver will try each resolver in order and return the first match
$stackableResolver = new yswery\DNS\Resolver\StackableResolver([$jsonResolver, $systemResolver]);

// Create the eventDispatcher and add the event subscribers
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\EchoLogger());

// Create a new instance of Server class
$server = new yswery\DNS\Server($stackableResolver, $eventDispatcher);

// Start DNS server
$server->start();

```
### Running example

* Run `composer install` to install dependencies
* Run `php example/example.php` to run the server

Query server using `dig` command to ensure proper functioning
```bash
$ dig @127.0.0.1 test.com A +short +noedns
111.111.111.111

$ dig @127.0.0.1 test.com TXT +short +noedns
"Some text."

$ dig @127.0.0.1 test2.com A +short +noedns
111.111.111.111
112.112.112.112
```
## Zone File Storage
PHP DNS Server supports three zone file formats out-of-the-box: JSON, XML, and YAML; each file format
is supported by a specialised `Resolver` class: `JsonResolver`, `XmlResolver`, and `YamlResolver`,
respectively. Example files are in the `example/` directory.

### JSON zone example
```json
{
  "domain": "example.com.",
  "default-ttl": 7200,
  "resource-records": [
    {
      "name": "@",
      "ttl": 10800,
      "type": "SOA",
      "class": "IN",
      "mname": "example.com.",
      "rname": "postmaster",
      "serial": 2,
      "refresh": 3600,
      "retry": 7200,
      "expire": 10800,
      "minimum": 3600
    }, {
      "type": "A",
      "address": "12.34.56.78"
    },{
      "type": "A",
      "address": "90.12.34.56"
    }, {
      "type": "AAAA",
      "address": "2001:acad:ad::32"
    }, {
      "name": "www",
      "type": "cname",
      "target": "@"
    }, {
      "name": "@",
      "type": "MX",
      "preference": 15,
      "exchange": "mail"
    }, {
      "name": "*.subdomain",
      "ttl": 3600,
      "type": "A",
      "address": "192.168.1.42"
    }
  ]
}
```

## Running Tests

Unit tests using PHPUnit are provided. A simple script is located in the root.

* run `composer install` to install PHPUnit and dependencies
* run `vendor/bin/phpunit` from the root to run the tests

## Supported Record Types

* A
* NS
* CNAME
* SOA
* PTR
* MX
* TXT
* AAAA
* OPT
* AXFR
* ANY

## License

The MIT License (MIT)

Copyright (c) 2016-2017 Yif Swery

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
