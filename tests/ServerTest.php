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

    public function testDs_encode_flags()
    {
        //Todo: Write test.
    }

    public function testDs_encode_label()
    {
        $input_1 = 'www.example.com.';
        $expectation_1 = chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $input_2 = '.';
        $expectation_2 = "\0";

        $input_3 = 'tld.';
        $expectation_3 = chr(3) . 'tld' . "\0";

        $methodName = 'ds_encode_label';

        $this->assertEquals($expectation_1, $this->server->invokePrivateMethod($methodName, $input_1));
        $this->assertEquals($expectation_2, $this->server->invokePrivateMethod($methodName, $input_2));
        $this->assertEquals($expectation_3, $this->server->invokePrivateMethod($methodName, $input_3));
    }

    public function testDs_encode_question_rr()
    {
        $input_1 = [[
            'qname' => 'www.example.com.',
            'qtype' => 1, //A Record
            'qclass' => 1, //IN
            ]];

        $expectation_1 =
            chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('nn', 1, 1);

        $input_2 = [[
            'qname' => 'domain.com.au.',
            'qtype' => 15, //MX Record
            'qclass' => 1, //IN
        ]];

        $expectation_2 =
            chr(6) . 'domain' . chr(3) . 'com' . chr(2) . 'au' . "\0" .
            pack('nn', 15, 1);

        $input_3 = [$input_1[0], $input_2[0]];
        $expectation_3 = $expectation_1 . $expectation_2;

        $methodName = 'ds_encode_question_rr';

        $this->assertEquals($expectation_1, $this->server->invokePrivateMethod($methodName,$input_1, 0));
        $this->assertEquals($expectation_2, $this->server->invokePrivateMethod($methodName,$input_2, 0));
        $this->assertEquals($expectation_3, $this->server->invokePrivateMethod($methodName,$input_3, 0));
    }

    public function testDs_encode_rr()
    {
        //Todo: Write test.
    }

    public function testDs_encode_type()
    {
        $input_1 = '192.168.0.1';
        $expectation_1 = inet_pton($input_1);

        $input_2 = '2001:acad:1337:b8::19';
        $expectation_2 = inet_pton($input_2);

        $input_3 = '192.168.1';
        $expectation_3 = str_repeat("\0", 4);

        $input_4 = '2001:acad:1337:b8:19';
        $expectation_4 = str_repeat("\0", 16);

        $input_5 = 'dns1.example.com.';
        $expectation_5 = chr(4) . 'dns1' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $input_6 = [
            'mname' => 'example.com.',
            'rname' => 'postmaster.example.com',
            'serial'=> 1970010188,
            'refresh' => 1800,
            'retry' => 7200,
            'expire' => 10800,
            'minimum-ttl' => 3600,
        ];
        $expectation_6 =
            chr(7) . 'example' . chr(3) . 'com' . "\0" .
            chr(10) . 'postmaster' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('NNNNN', 1970010188, 1800, 7200, 10800, 3600);

        $input_7 = 'mail.example.com.';
        $expectation_7 = pack('n', 10) . chr(4) . 'mail' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $input_8 = 'This is a comment.';
        $expectation_8 = chr(18) . $input_8;

        $methodName = 'ds_encode_type';

        $this->assertEquals($expectation_1, $this->server->invokePrivateMethod($methodName,1, $input_1, null));
        $this->assertEquals($expectation_2, $this->server->invokePrivateMethod($methodName,28, $input_2, null));
        $this->assertEquals($expectation_5, $this->server->invokePrivateMethod($methodName,2, $input_5, null));
        $this->assertEquals($expectation_6, $this->server->invokePrivateMethod($methodName,6, $input_6, null));
        $this->assertEquals($expectation_7, $this->server->invokePrivateMethod($methodName,15, $input_7, null));
        $this->assertEquals($expectation_8, $this->server->invokePrivateMethod($methodName,16, $input_8, null));

        //Todo: This test fails because the ds_error() method kills the code prior to handling malformed IP address
        //$this->assertEquals($expectation_3, $this->server->invokePrivateMethod($methodName,1, $input_3, null));
        //$this->assertEquals($expectation_4, $this->server->invokePrivateMethod($methodName,28, $input_4, null));
    }
}