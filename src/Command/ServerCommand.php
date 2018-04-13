<?php
/**
 * @package yswery\DNS
 */
namespace yswery\DNS\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use yswery\DNS\Event\ConsoleEventSubscriber;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\RecursiveResolver;
use yswery\DNS\Resolver\StackableResolver;
use yswery\DNS\Server;

/**
 * Class ServerCommand
 */
class ServerCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('phpdns')
            ->setDescription('PHP DNS server command script')
            ->setHelp('Use this script to start and control phpdns.');
    }

    /**
     * @inheritdoc
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resolver = new StackableResolver(
            [
                new JsonResolver('config/dns.example.json'),
                // JSON formatted DNS records file
                new RecursiveResolver(),
                // Recursive provider acting as a fallback to the JsonStorageProvider
            ]
        );

        $config = Yaml::parseFile('config/config.yml');

        $server = new Server($resolver, $config);

        $server->registerEventSubscriber(new ConsoleEventSubscriber($output));

        $server->start();
    }
}
