<?php

require '../vendor/autoload.php';

use Garden\Cli\Cli;

$cli = new Cli();

$cli
    ->opt('bind:b', 'Bind to a specific ip interface', false)
    ->opt('port:p', 'specify the port to bind to', false);

$args = $cli->parse($argv, true);

// defaults
$host = $args->getOpt('bind', '0.0.0.0');
$port = $args->getOpt('port', 53);


// Parse and return cli args.

// Create the eventDispatcher and add the event subscribers
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\Event\Subscriber\EchoLogger());

// Create a new instance of Server class
$server = new yswery\DNS\Server(null, $eventDispatcher, true, $host, $port);

// Start DNS server
$server->run();
