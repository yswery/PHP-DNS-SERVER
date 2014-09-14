<?php

// REGISTER AUTOLOADER
spl_autoload_register(function ($class) {
 $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . '.php';

 if (file_exists($file)) {
  require $file;
 }
});

use StorageProvider\JsonStorageProvider;

require "dns_server.class.php"; 

$record_file = 'dns_record.json';
$storage = new JsonStorageProvider($record_file);

// Creating a new instance of our class
$dns = new PHP_DNS_SERVER($storage);

// Starting our DNS server
$dns->start();


?>
