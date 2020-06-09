<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Event;

use React\Datagram\SocketInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ServerStartEvent extends Event
{
    /**
     * @var SocketInterface
     */
    private $socket;

    /**
     * ServerStartEvent constructor.
     *
     * @param SocketInterface $socket
     */
    public function __construct(SocketInterface $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return SocketInterface
     */
    public function getSocket(): SocketInterface
    {
        return $this->socket;
    }
}
