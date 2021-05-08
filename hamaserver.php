<?php
$vars = array(
	'SERVER_IP' => '0.0.0.0',
	'SERVER_PORT' => 53,
	'RADIO_DOMAIN' => 'radio.example.com',
	'ALLOWED_DOMAIN' => 'all',
	'TIME_SERVER' => 'ntp0.fau.de'
);
foreach($vars as $k => &$v ){
	if( isset( $_ENV[$k] ) ){
		$v = $_ENV[$k];
	}
}

require_once __DIR__.'/vendor/autoload.php';

$systemResolver = new yswery\DNS\Resolver\SystemResolver();

$hamaResolver = new yswery\DNS\Resolver\HamaResolver($systemResolver, $vars['RADIO_DOMAIN'], $vars['TIME_SERVER']);

$stackableResolver = new yswery\DNS\Resolver\StackableResolver([$hamaResolver, $systemResolver]);

$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addSubscriber(new \yswery\DNS\Event\Subscriber\EchoLogger());
$eventDispatcher->addSubscriber(new \yswery\DNS\Event\Subscriber\ServerTerminator());

$server = new yswery\DNS\Server(
	$stackableResolver, $eventDispatcher,
	null, null, false,
	$vars['SERVER_IP'], $vars['SERVER_PORT'], $vars['ALLOWED_DOMAIN']
);

$server->start();
