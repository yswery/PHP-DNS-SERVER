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

use React\Datagram\Socket;
use Symfony\Component\EventDispatcher\Event;

class ServerStartEvent extends Event
{
    /**
     * @var Socket
     */
    private $socket;

    /**
     * ServerStartEvent constructor.
     *
     * @param Socket $socket
     */
    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return Socket
     */
    public function getSocket(): Socket
    {
        return $this->socket;
    }
}
