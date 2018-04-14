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
     * @param InputInterface  $input
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

        $server = new Server($resolver, $config);

        $server->registerEventSubscriber(new ConsoleEventSubscriber($output));

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
}
