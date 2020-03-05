<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Event\Subscriber;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use yswery\DNS\Event\Events;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerExceptionEvent;
use yswery\DNS\Event\ServerStartEvent;

class EchoLogger extends AbstractLogger implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::SERVER_START => 'onServerStart',
            Events::SERVER_START_FAIL => 'onException',
            Events::SERVER_EXCEPTION => 'onException',
            Events::QUERY_RECEIVE => 'onQueryReceive',
            Events::QUERY_RESPONSE => 'onQueryResponse',
        ];
    }

    public function onServerStart(ServerStartEvent $event): void
    {
        $this->log(LogLevel::INFO, 'Server started.');
        $this->log(LogLevel::INFO, sprintf('Listening on %s', $event->getSocket()->getLocalAddress()));
    }

    public function onException(ServerExceptionEvent $event): void
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
