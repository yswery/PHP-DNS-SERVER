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

use Symfony\Contracts\EventDispatcher\Event;

class ServerExceptionEvent extends Event
{
    /**
     * @var \Exception
     */
    private $exception;

    /**
     * ExceptionEvent constructor.
     *
     * @param \Exception $exception
     */
    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }
}
