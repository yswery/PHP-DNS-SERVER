<?php

require_once __DIR__.'/../vendor/autoload.php';

$stackableResolver = new yswery\DNS\Resolver\StackableResolver([
    new yswery\DNS\Resolver\GoogleDnsResolver()
]);

$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\EchoLogger());

$server = new yswery\DNS\Server($stackableResolver, $eventDispatcher);

$server->start();
