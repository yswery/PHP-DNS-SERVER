<?php

require "vendor/autoload.php";

// Creating a new instance of our class
$dns = new yswery\DNS\Server((new yswery\DNS\Resolver\GoogleResolver()));

// Starting our DNS server
$dns->start();
