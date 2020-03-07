<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Event\Subscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class EchoLogger extends LoggerSubscriber implements LoggerInterface
{
    use LoggerTrait;

    public function __construct()
    {
        $this->setLogger($this);
    }

    public function log($level, $message, array $context = [])
    {
        echo sprintf('[%s] %s: %s'.PHP_EOL, date('c'), $level, $message);
    }
}
