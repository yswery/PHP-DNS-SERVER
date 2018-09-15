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

class MessageEvent extends ServerStartEvent
{
    /**
     * @var string
     */
    private $remote;

    /**
     * @var string
     */
    private $message;

    /**
     * MessageEvent constructor.
     *
     * @param Socket $socket
     * @param string $remote
     * @param string $message
     */
    public function __construct(Socket $socket, string $remote, string $message)
    {
        parent::__construct($socket);
        $this->remote = $remote;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getRemote(): string
    {
        return $this->remote;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
