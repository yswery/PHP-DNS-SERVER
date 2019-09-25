<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
