<?php

namespace yswery\DNS\Event;

use yswery\DNS\Message;

class QueryReceiveEvent extends Event
{
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
