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

use yswery\DNS\JsonResolver;
use yswery\DNS\Server;

class TestServerProxy
{
    private static $name = 'yswery\DNS\Server';

    /**
     * @var Server
     */
    protected $server;

    public function __construct()
    {
        $storage = new JsonResolver(__DIR__ . '/test_records.json');
        $this->server = new Server($storage);
    }

    public function ds_encode_label($str, $offset = null)
    {
        $method = new \ReflectionMethod(self::$name, 'ds_encode_label');
        $method->setAccessible(true);

        return $method->invoke($this->server, $str, $offset);
    }
}