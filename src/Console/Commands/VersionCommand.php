<?php

namespace yswery\DNS\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use yswery\DNS\Server;

class VersionCommand extends Command
{
    protected static $defaultName = "version";

    protected function configure()
    {
        $this->setDescription('Shows the current PhpDnsServer version')
            ->setHelp('Shows the current PhpDnsServer version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('PowerDnsServer version '.Server::VERSION);
    }
}