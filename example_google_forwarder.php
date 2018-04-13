<?php

require "vendor/autoload.php";

// Creating a new instance of our class
$dns = new yswery\DNS\Server((new yswery\DNS\GoogleResolver()));

// Starting our DNS server
$dns->start();
