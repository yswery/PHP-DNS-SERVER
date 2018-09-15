<?php

namespace yswery\DNS\Event;

use React\Datagram\Socket;

class MessageEvent extends ServerStartEvent
{
    private $remote;

    private $message;

    public function __construct(Socket $socket, string $remote, string $message)
    {
        parent::__construct($socket);
        $this->remote = $remote;
        $this->message = $message;
    }

    public function getRemote(): string
    {
        return $this->remote;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
