<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use yswery\DNS\Event\Event;
use yswery\DNS\Event\EventSubscriberInterface;
use yswery\DNS\Event\ExceptionEvent;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerStartEvent;

class EchoLogger extends AbstractLogger implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Event::SERVER_START => 'onServerStart',
            Event::EXCEPTION => 'onException',
            Event::QUERY_RECEIVE => 'onQueryReceive',
            Event::QUERY_RESPONSE => 'onQueryResponse',
        ];
    }

    public function onServerStart(ServerStartEvent $event): void
    {
        $this->log(LogLevel::INFO, 'Server started.');
    }

    public function onException(ExceptionEvent $event): void
    {
        $this->log(LogLevel::ERROR, $event->getException()->getMessage());
    }

    public function onQueryReceive(QueryReceiveEvent $event): void
    {
        foreach ($event->getMessage()->getQuestions() as $question) {
            $this->log(LogLevel::INFO, 'Query: '.$question);
        }
    }

    public function onQueryResponse(QueryResponseEvent $event): void
    {
        foreach ($event->getMessage()->getAnswers() as $answer) {
            $this->log(LogLevel::INFO, 'Answer: '.$answer);
        }
    }

    public function log($level, $message, array $context = [])
    {
        echo sprintf('[%s] %s: %s'.PHP_EOL, date('c'), $level, $message);
    }
}
