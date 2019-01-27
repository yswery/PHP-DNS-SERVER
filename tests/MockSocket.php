<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Tests;

use Evenement\EventEmitterTrait;
use React\Datagram\SocketInterface;

class MockSocket implements SocketInterface
{
    use EventEmitterTrait;

    private $transmissions = [];

    public function send($data, $remoteAddress = null)
    {
        $this->transmissions[] = $data;
    }

    public function getLastTransmission(): string
    {
        return end($this->transmissions);
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function end()
    {
        // TODO: Implement end() method.
    }

    public function resume()
    {
        // TODO: Implement resume() method.
    }

    public function pause()
    {
        // TODO: Implement pause() method.
    }

    public function getLocalAddress()
    {
        // TODO: Implement getLocalAddress() method.
    }

    public function getRemoteAddress()
    {
        // TODO: Implement getRemoteAddress() method.
    }
}
