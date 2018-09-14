<?php

namespace yswery\DNS\Tests;

use yswery\DNS\Event\Event;
use yswery\DNS\Event\EventSubscriberInterface;
use yswery\DNS\Event\ExceptionEvent;
use yswery\DNS\Event\MessageEvent;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerStartEvent;

class ExampleEventSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Event::SERVER_START => 'onServerStart',
            Event::EXCEPTION => 'onException',
            Event::MESSAGE => 'onMessage',
            Event::QUERY_RECEIVE => 'onQueryReceive',
            Event::QUERY_RESPONSE => 'onQueryResponse',
        ];
    }

    public function onServerStart(ServerStartEvent $event): void
    {
    }

    public function onException(ExceptionEvent $event): void
    {
    }

    public function onMessage(MessageEvent $event): void
    {
    }

    public function onQueryReceive(QueryReceiveEvent $event): void
    {
    }

    public function onQueryResponse(QueryResponseEvent $event): void
    {
    }
}
