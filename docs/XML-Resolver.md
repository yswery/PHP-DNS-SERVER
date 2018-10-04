# XML Resolver
The `XMLResolver` provides the capability to store DNS records as XML files.

The XSD file is in the root of the project directory.

## File structure
The object MUST declare the `<name>`, `<default-ttl>` and `<resource-records>` tags.
Each `<resource-record>` within `<resource-records>` can have the following properties:
 * **name** - optional string, if none or an `@` is specified, the name will default to the zone name.
 This does not need to be a fully qualified name, as the parent will be automatically appended.
 * **ttl** - optional int, if none is specified it will default to the `<default-ttl>`.
 * **type** - required string, the RDATA type. This MUST be specified.
 * **rdata** - required
 
### Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<domain
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="http://example.com/php-dns-server.xsd"
>
    <name>test2.com.</name>
    <default-ttl>300</default-ttl>

    <resource-records>
        <resource-record>
            <name>@</name>
            <ttl>10800</ttl>
            <type>SOA</type>
            <class>IN</class>
            <rdata>
                <mname>ns1</mname>
                <rname>admin</rname>
                <serial>2014111100</serial>
                <refresh>1800</refresh>
                <retry>7200</retry>
                <expire>8600</expire>
                <minimum>300</minimum>
            </rdata>
        </resource-record>

        <resource-record>
            <type>A</type>
            <rdata>
                <address>111.111.111.111</address>
            </rdata>
        </resource-record>

        <resource-record>
            <type>A</type>
            <rdata>
                <address>112.112.112.112</address>
            </rdata>
        </resource-record>

        <resource-record>
            <type>MX</type>
            <rdata>
                <preference>20</preference>
                <exchange>mail-gw1</exchange>
            </rdata>
        </resource-record>

        <resource-record>
            <type>MX</type>
            <rdata>
                <preference>30</preference>
                <exchange>mail-gw2</exchange>
            </rdata>
        </resource-record>

        <resource-record>
            <type>TXT</type>
            <rdata>
                <text>Some text.</text>
            </rdata>
        </resource-record>
    </resource-records>
</domain>
```

## Processing Records
```php
$files = [__DIR__.'/example.com.xml', __DIR__.'/test.com.xml'];
$resolver = new yswery\DNS\Resolver\XmlResolver($files);
$resolver->getAnswer(/*some query*/);
```