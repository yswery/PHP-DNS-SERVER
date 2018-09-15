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
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Encoder;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestServerProxy
     */
    private $server;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $storage = new JsonResolver(__DIR__ . '/test_records.json');
        $this->server = new TestServerProxy($storage);
    }

    /**
     * @param $name
     * @param $type
     * @param $id
     * @return array
     * @throws \ReflectionException
     */
    private function encodeQuery($name, $type, $id)
    {
        $qname = Encoder::encodeLabel($name);
        $flags = 0b0000000000000000;
        $header = pack('nnnnnn', $id, $flags, 1, 0, 0, 0);
        $question = $qname . pack('nn', $type, 1);

        return [$header, $question];
    }

    /**
     * @throws \ReflectionException
     */
    public function testDs_handle_query()
    {
        list($queryHeader, $question) = $this->encodeQuery($name = 'test.com.', RecordTypeEnum::TYPE_A, $id = 1337);
        $packet = $queryHeader . $question;

        $flags = 0b1000010000000000;
        $qname = Encoder::encodeLabel($name);
        $header = pack('nnnnnn', $id, $flags, 1, 1, 0, 0);

        $rdata = inet_pton('111.111.111.111');
        $answer = $qname . pack('nnNn', 1, 1, 300, strlen($rdata)) . $rdata;

        $expectation = $header . $question . $answer;

        $this->assertEquals($expectation, $this->server->invokePrivateMethod('ds_handle_query', $packet));
    }
}