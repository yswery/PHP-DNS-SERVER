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
    private static $name = '\\yswery\\DNS\\Server';

    /**
     * @var Server
     */
    protected $server;

    public function __construct()
    {
        $storage = new JsonResolver(__DIR__ . '/test_records.json');
        $this->server = new Server($storage);
    }

    public function ds_encode_flags($flags)
    {
        return $this->invokePrivateMethod('ds_encode_flags', $flags);
    }

    public function ds_encode_label($str, $offset = null)
    {
        return $this->invokePrivateMethod('ds_encode_label', $str, $offset);
    }

    public function ds_encode_question_rr($list, $offset)
    {
        return $this->invokePrivateMethod('ds_encode_question_rr', $list, $offset);
    }

    public function ds_encode_rr($list, $offset)
    {
        return $this->invokePrivateMethod('ds_encode_question_rr', $list, $offset);
    }

    public function ds_encode_type($type, $val = null, $offset = null)
    {
        return $this->invokePrivateMethod('ds_encode_type', $type, $val, $offset);
    }

    private function invokePrivateMethod($methodName, ...$params)
    {
        $method = new \ReflectionMethod(self::$name, $methodName);
        $method->setAccessible(true);

        return $method->invoke($this->server, ...$params);
    }
}