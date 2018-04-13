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

use yswery\DNS\Server;
use yswery\DNS\Resolver\JsonResolver;

/**
 * Class TestServerProxy
 */
class TestServerProxy
{
    private static $name = 'yswery\DNS\Server';

    /**
     * @var Server
     */
    protected $server;

    /**
     * TestServerProxy constructor.
     */
    public function __construct()
    {
        $storage = new JsonResolver(__DIR__ . '/data/dns.records.json');
        $this->server = new Server($storage);
    }

    /**
     * @param $str
     * @param null $offset
     * @return mixed
     */
    public function encodeLabel($str, $offset = null)
    {
        $method = new \ReflectionMethod(self::$name, 'encodeLabel');
        $method->setAccessible(true);

        return $method->invoke($this->server, $str, $offset);
    }
}