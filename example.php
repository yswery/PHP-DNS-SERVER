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
