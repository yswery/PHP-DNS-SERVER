<?php

namespace yswery\DNS\Event;

abstract class Event
{
    public const SERVER_START = ServerStartEvent::class;
    public const EXCEPTION = ExceptionEvent::class;
    public const MESSAGE = MessageEvent::class;
    public const QUERY_RECEIVE = QueryReceiveEvent::class;
    public const QUERY_RESPONSE = QueryResponseEvent::class;
}
