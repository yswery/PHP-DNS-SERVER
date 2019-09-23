<?php

namespace yswery\DNS\Console;

use Symfony\Component\Console\Application;
use yswery\DNS\Console\Commands\VersionCommand;
use yswery\DNS\Server;

class CommandServer extends Application
{
    public function __construct()
    {
        parent::__construct('PhpDnsServer', Server::VERSION);

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->add(new VersionCommand());
    }
}