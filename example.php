<?php

require "vendor/autoload.php";

use Symfony\Component\Yaml\Yaml;
use yswery\DNS\StackableResolver;
use yswery\DNS\Server;
use yswery\DNS\RecursiveResolver;
use yswery\DNS\JsonResolver;

// instantiate resolver
$resolver = new StackableResolver(
    [
        new JsonResolver('config/dns.example.json'), // JSON formatted DNS records file
        new RecursiveResolver(),                                       // Recursive provider acting as a fallback to the JsonStorageProvider
    ]
);

// load configuration
$config = Yaml::parseFile('config/config.yml');

// Creating a new instance of server and start it
(new Server($resolver, $config))->start();
