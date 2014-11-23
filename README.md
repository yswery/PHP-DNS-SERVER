[![Build Status](https://travis-ci.org/yswery/PHP-DNS-SERVER.svg?branch=master)](https://travis-ci.org/yswery/PHP-DNS-SERVER)
[![Coverage Status](https://coveralls.io/repos/yswery/PHP-DNS-SERVER/badge.png?branch=master)](https://coveralls.io/r/yswery/PHP-DNS-SERVER?branch=master)


PHP DNS Server
==============

This is an Authoritative DNS Server written in pure PHP.
It will listen to DNS request on the default port (Default: port 53) and give answers about any donamin that it has DNS records for.
This class can be used to give DNS responses dynamically based on your pre-existing PHP code.

Support Record Types
====================

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

PHP Requirements
================

* `PHP 5.3+`
* Needs either `sockets` or `socket_create` PHP extension loaded (which they are by default)

Example:
========
Here is an example of some DNS records
```
<?php

require "vendor/autoload.php";

// JSON formatted DNS records file
$record_file = 'dns_record.json';
$jsonStorageProvider = new yswery\DNS\JsonStorageProvider($record_file);

// Recursive provider acting as a fallback to the JsonStorageProvider
$recursiveProvider = new yswery\DNS\RecursiveProvider($options);

$stackableResolver = new yswery\DNS\StackableResolver(array($jsonStorageProvider, $recursiveProvider));

// Creating a new instance of our class
$dns = new yswery\DNS\Server($stackableResolver);

// Starting our DNS server
$dns->start();
```

And Here is us querying it and seeing the response
```
$ dig @127.0.0.1 test.com A +short
111.111.111.111

$ dig @127.0.0.1 test.com TXT +short
"Some text."

$ dig @127.0.0.1 test2.com A +short
111.111.111.111
112.112.112.112
```

##Running Tests

Unit tests using PHPUnit are provided. A simple script is located in the root.

* run composer install --dev to install PHPUnit and dependencies
* run ./phpunit from the root to run the tests
