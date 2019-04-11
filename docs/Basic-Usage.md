Basic Usage
===========

```php
// JsonResolver created and provided with path to file with json dns records
$jsonResolver = new yswery\DNS\Resolver\JsonResolver(['/path/to/example.com.json', '/path/to/test.com.json']);

// Create a new instance of Server class
$server = new yswery\DNS\Server($jsonResolver);

// Start DNS server
$server->start();
```
