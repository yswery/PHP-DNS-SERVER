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
<?

require "dns_server.class.php";

// dns records
$ds_records = array(
    'test.com' => array(
        'A' => '111.111.111.111',
        'MX' => '112.112.112.112',
        'NS' => 'ns1.test.com',
        'TXT' => 'Some text.'
    ),
    'test2.com' => array(
        // allow multiple records of same type
        'A' => array(
            '111.111.111.111',
            '112.112.112.112'
        )
    )
);

// Creating a new instance of our class
$dns = new PHP_DNS_SERVER($ds_records);

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

