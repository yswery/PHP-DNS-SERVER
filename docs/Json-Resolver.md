# Enhanced JSON Resolver
The `EnhancedJsonResolver` treats records differently to its predecessor. Each JSON file is more akin to
a BIND zone in how it structured.

## File structure
The object MUST declare the `domain`, `default-ttl` and array of `resource-records` objects.
Each Resource Record object can have the following properties:
 * **name** - optional string, if none or an `@` is specified, the name will default to the zone name.
 This does not need to be a fully qualified name, as the parent will be automatically appended.
 * **ttl** - optional int, if none is specified it will default to the `default-ttl`.
 * **type** - string, the RDATA type. This must be specified.
 * **address** - string (A and AAAA records only)
 * **target** - string (NS, CNAME and PTR records)
 * **mname** (string), **rname** (string), **serial** (int), **refresh** (int), **retry** (int),\
 **expire** (int) **minimum** (int) - SOA records
 * **preference** (int) and **exchange** (string) - MX records
 
### Example

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
    }
  ]
}
```

## Processing Records
```php
$files = [__DIR__.'/example.com.json', __DIR__.'/test.com.json'];
$resolver = new yswery\DNS\Resolver\EnhancedJsonResolver($files);
$resolver->getAnswer(/*some query*/);
```

## Backward Compatibility
The Enhanced JSON Resolver can handle the older format JSON zone records (example below). These are loaded
the same way as the new file format.

```json
{
  "test.com": {
    "A": "111.111.111.111",
    "MX": [
      {
        "exchange": "mail-gw1.test.com",
        "preference": 10
      },
      {
        "exchange": "mail-gw2.test.com",
        "preference": 20
      }
    ],
    "NS": [
      "ns1.test.com",
      "ns2.test.com"
    ],
    "TXT": "Some text.",
    "AAAA": "DEAD:01::BEEF",
    "CNAME": "www2.test.com",
    "SOA": [
      {
        "mname": "ns1.test.com",
        "rname": "admin.test.com",
        "serial": "2014111100",
        "retry": "7200",
        "refresh": "1800",
        "expire": "8600",
        "minimum": "300"
      }
    ]
  },
  "test2.com": {
    "A": [
      "111.111.111.111",
      "112.112.112.112"
    ],
    "MX": [
      {
        "preference": 20,
        "exchange": "mail-gw1.test2.com."
      },
      {
        "preference": 30,
        "exchange": "mail-gw2.test2.com."
      }
    ]
  }
}

```