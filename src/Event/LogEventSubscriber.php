<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Event;

use Psr\Log\LoggerInterface;

/**
 * Class LogEventSubscriber
 */
class LogEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LogEventSubscriber constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $data
     */
    public function onEvent(array $data)
    {
        $query = $data['query'][0]['qname'];

        $this->logger->info("Requested to resolve $query");
    }

    /**
     * @param array $data
     *
     * @deprecated
     */
    public function onError(array $data)
    {
        // @todo implement monoglog
    }
}
