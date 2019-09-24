<?php

require '../vendor/autoload.php';

echo "Preparing installation of PhpDnsServer.\n";

// detect os
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $isWindows = true;
    echo "Detected windows environment, please note that running the installer on windows is not fully supported at this time.";
} else {
    $isWindows = false;
}

// make sure we are running as root if os is linux
if (!$isWindows) {
    if (posix_getuid() != 0) {
        die("This script must be run as root.\n");
    }

    // create the default config file
    $defaultConfig = [
        'host' => '0.0.0.0',
        'port' => 53,
        'storage' => '/etc/phpdnsserver',
        'backend' => 'file'
    ];

    $filesystem = new \Symfony\Component\Filesystem\Filesystem;

    try {
        echo "Creating required directories and config files...\n";

        $filesystem->mkdir('/etc/phpdnsserver');
        $filesystem->mkdir('/etc/phpdnsserver/zones');
        $filesystem->mkdir('/etc/phpdnsserver/logs');

        // create default config
        file_put_contents('/etc/phpdns.json', json_encode(getcwd()));
    } catch (\Symfony\Component\Filesystem\Exception\IOException $e){
        die("An error occurred during installation\n".$e->getMessage());
    }

} else {
    // create the default config file
    $defaultConfig = [
        'host' => '0.0.0.0',
        'port' => 53,
        'backend' => 'file'
    ];

    $filesystem = new \Symfony\Component\Filesystem\Filesystem;

    try {
        echo "Creating required directories and config files...\n";

        $filesystem->mkdir(getcwd().'\\zones');
        $filesystem->mkdir(getcwd().'\\logs');

        // create default config
        file_put_contents(getcwd().'\\phpdns.json', json_encode(getcwd()));
    } catch (\Symfony\Component\Filesystem\Exception\IOException $e){
        die("An error occurred during installation\n".$e->getMessage());
    }
}