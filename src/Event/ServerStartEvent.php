<?php

namespace yswery\DNS\Event;

use React\Datagram\Socket;

class ServerStartEvent extends Event
{
    private $socket;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    public function getSocket(): Socket
    {
        return $this->socket;
    }
}
