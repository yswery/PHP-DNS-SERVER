<?php

require __DIR__.'/../vendor/autoload.php';

// JSON resolver
$jsonResolver = new yswery\DNS\JsonResolver(__DIR__.'/record.json');

// Recursive resolver acting as a fallback to the JsonResolver
$recursiveResolver = new yswery\DNS\RecursiveResolver;

$stackableResolver = new yswery\DNS\StackableResolver([$jsonResolver, $recursiveResolver]);

// Create a new instance of Server class
$server = new yswery\DNS\Server($stackableResolver);

// Start DNS server
$server->start();
