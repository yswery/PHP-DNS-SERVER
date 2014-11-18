<?php

require "vendor/autoload.php";

$record_file = 'dns_record.json';
$storage = new yswery\DNS\JsonStorageProvider($record_file);

// Creating a new instance of our class
$dns = new yswery\DNS\Server($storage);

// Starting our DNS server
$dns->start();
