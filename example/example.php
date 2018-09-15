<?php

require_once __DIR__.'/../vendor/autoload.php';

// JsonResolver created and provided with path to file with json dns records
$jsonResolver = new yswery\DNS\Resolver\JsonResolver(__DIR__.'/record.json');

// System resolver acting as a fallback to the JsonResolver
$systemResolver = new yswery\DNS\Resolver\SystemResolver();

// StackableResolver will try each resolver in order and return the first match
$stackableResolver = new yswery\DNS\Resolver\StackableResolver([$jsonResolver, $systemResolver]);

// Create a new instance of Server class
$server = new yswery\DNS\Server($stackableResolver);

// Log to the console, you can use any PSR logger such as Monolog
$server->registerEventSubscriber(new \yswery\DNS\EchoLogger());

// Start DNS server
$server->start();
