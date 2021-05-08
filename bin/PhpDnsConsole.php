<?php

require '../vendor/autoload.php';

// Create the eventDispatcher and add the event subscribers
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\Event\Subscriber\EchoLogger());

// Create a new instance of Server class
$server = new yswery\DNS\Console\CommandServer();

// Start DNS server
$server->run();