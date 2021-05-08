<?php

require '../vendor/autoload.php';

use Garden\Cli\Cli;

// parse cli args
$cli = new Cli();

$cli
    ->opt('bind:b', 'Bind to a specific ip interface', false)
    ->opt('port:p', 'specify the port to bind to', false)
    ->opt('config:c', 'specify the path to the phpdns.json file', false)
    ->opt('storage:s', 'specify the location to zone storage folder', false);

$args = $cli->parse($argv, true);

// defaults
$host = $args->getOpt('bind', '0.0.0.0');
$port = $args->getOpt('port', 53);

// figure out config location
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // default to current working directory on windows
    $configFile = $args->getOpt('config', getcwd() . '/phpdns.json');
    $storageDirectory = $args->getOpt('storage', getcwd());
} else {
    // default to /etc/phpdns.json and /etc/phpdnsserver if not on windows
    $configFile = $args->getOpt('config', '/etc/phpdns.json');
    $storageDirectory = $args->getOpt('storage', '/etc/phpdnserver');
}

// initialize the configuration
$config = new yswery\DNS\Config\FileConfig($configFile);
try {
    $config->load();

    if ($config->has('host')) {
        $host = $config->get('host');
    }

    if ($config->has('port')) {
        $port = $config->get('port');
    }

    if ($config->has('storage')) {
        $storageDirectory = $config->get('storage');
    }
} catch (\yswery\DNS\Exception\ConfigFileNotFoundException $e) {
    echo $e->getMessage();
}

// Create the eventDispatcher and add the event subscribers
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\Event\Subscriber\EchoLogger());

try {
    // Create a new instance of Server class
    $server = new yswery\DNS\Server(null, $eventDispatcher, $config, $storageDirectory, true, $host, $port);

    // Start DNS server
    $server->run();
} catch (\Exception $e) {
    echo $e->getMessage();
}
