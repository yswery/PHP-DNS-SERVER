<?php

require "vendor/autoload.php";

use Symfony\Component\Yaml\Yaml;
use yswery\DNS\StackableResolver;
use yswery\DNS\Server;
use yswery\DNS\RecursiveProvider;
use yswery\DNS\JsonStorageProvider;

// instantiate resolver
$resolver = new StackableResolver(
    [
        new JsonStorageProvider('config/dns.example.json'), // JSON formatted DNS records file
        new RecursiveProvider()                                       // Recursive provider acting as a fallback to the JsonStorageProvider
    ]
);

// load configuration
$config = Yaml::parseFile('config/config.yml');

// Creating a new instance of server and start it
(new Server($resolver, $config))->start();