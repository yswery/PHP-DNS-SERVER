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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use yswery\DNS\Event\Events;
use yswery\DNS\Event\ServerExceptionEvent;

class ServerTerminator implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::SERVER_START_FAIL => 'onException',
        ];
    }

    public function onException(ServerExceptionEvent $event): void
    {
        exit(1);
    }
}
