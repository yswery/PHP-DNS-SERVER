<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Event;

use Symfony\Component\Console\Output\OutputInterface;
use yswery\DNS\RecordTypeEnum;

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
            $type = RecordTypeEnum::getName($data['query'][0]['qtype']);
            $domain = $data['query'][0]['qname'];
            $time = (new \DateTime())->format('Y-m-d H:i:s');

            $message = "[$time] Requested to resolve $type query for domain $domain";

            if (isset($data['answer'])) {
                $message .= ' Result: '.$data['answer'][0]['data']['value'];
            }

            $this->output->writeln("<info>$message</info>");
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