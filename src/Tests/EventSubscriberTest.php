<?php

namespace yswery\DNS\Tests;

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\Encoder;
use yswery\DNS\Event\Event;
use yswery\DNS\Message;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\Server;

class EventSubscriberTest extends TestCase
{
    public function testSubscriberCalls(): void
    {
        $q_RR = (new ResourceRecord())
            ->setName('test.com.')
            ->setType(RecordTypeEnum::TYPE_OPT)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $query = new Message();
        $query->setQuestions([$q_RR])
            ->getHeader()
            ->setQuery(true)
            ->setId($id = 1337);

        $queryEncoded = Encoder::encodeMessage($query);

        $subscriber = $this->createMock(ExampleEventSubscriber::class);
        $subscriber->method('getSubscribedEvents')->with()->willReturn([
            Event::SERVER_START => 'onServerStart',
            Event::EXCEPTION => 'onException',
            Event::MESSAGE => 'onMessage',
            Event::QUERY_RECEIVE => 'onQueryReceive',
            Event::QUERY_RESPONSE => 'onQueryResponse',
        ]);

        $subscriber->expects($this->atLeastOnce())->method('getSubscribedEvents');
        $subscriber->expects($this->once())->method('onQueryReceive');
        $subscriber->expects($this->once())->method('onQueryResponse');

        $server = new Server(new DummyResolver());
        $server->registerEventSubscriber($subscriber);
        $server->handleQueryFromStream($queryEncoded);
    }

    public function testInvalidEventMapping(): void
    {
        $subscriber = $this->createMock(ExampleEventSubscriber::class);
        $subscriber->method('getSubscribedEvents')->with()->willReturn([
            Event::SERVER_START => 'nonExistingMethod',
        ]);

        $server = new Server(new DummyResolver());

        $this->expectException(\BadMethodCallException::class);
        $server->registerEventSubscriber($subscriber);
    }
}
