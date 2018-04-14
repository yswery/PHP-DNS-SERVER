<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Command;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Registry;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use yswery\DNS\Event\ConsoleEventSubscriber;
use yswery\DNS\Event\LogEventSubscriber;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\RecursiveResolver;
use yswery\DNS\Resolver\StackableResolver;
use yswery\DNS\Resolver\YamlResolver;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resolver = new StackableResolver(
            [
                // Yaml formatted DNS records file
                new YamlResolver('config/dns.example.yml'),
                // JSON formatted DNS records file
                new JsonResolver('config/dns.example.json'),
                // Recursive provider acting as a fallback to the YamlResolver and JsonResolver
                new RecursiveResolver(),
            ]
        );

        $config = Yaml::parseFile('config/config.yml');

        $server = new Server($resolver, isset($config['server']) ? $config['server'] : []);

        $server->registerEventSubscriber(new ConsoleEventSubscriber($output));

        if (isset($config['log']) && isset($config['log']['enabled']) && $config['log']['enabled']) {
            $server->registerEventSubscriber(new LogEventSubscriber($this->getLogger($config['log'])));
        }

        try {
            $server->run();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if ($output->isVeryVerbose()) {
                $message .= "\n".$e->getFile().':'.$e->getLine();
            }

            $output->writeln("<error>$message</error>");
        }
    }

    /**
     * @param array $config
     *
     * @return Logger
     */
    protected function getLogger($config = [])
    {
        $logger = new Logger('application');
        $logger->pushHandler(new StreamHandler($config['path'], Logger::DEBUG));

        Registry::addLogger($logger);

        return $logger;
    }
}
