<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Event;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsoleEventSubscriber
 */
class ConsoleEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * ConsoleEventSubscriber constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param array $data
     */
    public function onEvent(array $data)
    {
        if ($this->output->isVerbose()) {
            $query = $data['query'][0]['qname'];

            $this->output->writeln("<info>Requested to resolve $query</info>");
        }
    }

    /**
     * @param array $data
     *
     * @deprecated
     */
    public function onError(array $data)
    {
        $this->output->writeln("<error>{$data['error']}</error>");

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln($data['file'].':'.$data['line']);
        }
    }
}