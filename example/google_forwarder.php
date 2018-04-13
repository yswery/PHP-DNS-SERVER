<?php

require "../vendor/autoload.php";

use yswery\DNS\Server;
use yswery\DNS\Resolver\GoogleResolver;

// Creating a new instance of our class
$dns = new Server((new GoogleResolver()));

// Starting our DNS server
$dns->start();
