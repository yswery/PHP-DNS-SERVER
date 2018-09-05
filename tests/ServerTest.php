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

    /**
     * Test the ds_encode_flags() and ds_decode_flags() methods.
     *
     * @throws \ReflectionException
     */
    public function test_encode_decode_flags()
    {
        $flags = [
            'qr' => 1,      //1 bit
            'opcode' => 0,  //4 bits
            'aa' => 1,      //1 bit
            'tc' => 0,      //1 bit
            'rd' => 0,      //1 bit
            'ra' => 0,      //1 bit
            'z' => 0,       //3 bits
            'rcode' => 0,   //4 bits
        ];

        $encoded = 0b1000010000000000;

        $this->assertEquals($encoded, $this->server->invokePrivateMethod('ds_encode_flags', $flags));
        $this->assertEquals($flags, $this->server->invokePrivateMethod('ds_decode_flags', $encoded));
    }

    /**
     * Cannot write test as one cannot pass by reference using a reflection method.
     *
     * @Todo Write test case.
     */
    public function testDs_decode_label()
    {
        //Todo: Write test.
    }

    /**
     * @throws \ReflectionException
     */
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

    /**
     * Cannot write test as one cannot pass by reference using a reflection method.
     *
     * @Todo Write test case.
     */
    public function testDs_decode_question_rr()
    {
    }

    /**
     * @throws \ReflectionException
     */
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

    /**
     * Cannot write test as one cannot pass by reference using a reflection method.
     *
     * @Todo Write test case.
     */
    public function testDs_decode_rr()
    {
    }

    /**
     * @throws \ReflectionException
     */
    public function testDs_encode_rr()
    {
        $name = 'example.com.';
        $nameEncoded = $this->server->invokePrivateMethod('ds_encode_label', $name);
        $exchange = 'mail.example.com.';
        $exchangeEncoded = $this->server->invokePrivateMethod('ds_encode_label', $exchange);
        $priority = 10;
        $ttl = 1337;
        $class = 1; //INTERNET
        $type = 15; //MX
        $ipAddress = '192.163.5.2';

        $rdata = pack('n', $priority) . $exchangeEncoded;
        $rdata2 = inet_pton($ipAddress);

        $decoded = $decoded2 = [
            'name' => $name,
            'class' => $class,
            'ttl' => $ttl,
            'data' => [
                'type' => $type,
                'value' => [
                    'priority' => $priority,
                    'target' => $exchange,
                ],
            ],
        ];

        $decoded2['data'] = [
            'type' => 1,
            'value' => $ipAddress,
        ];

        $encoded = $nameEncoded . pack('nnNn', $type, $class, $ttl, strlen($rdata)) . $rdata;
        $encoded2 = $nameEncoded . pack('nnNn', 1, $class, $ttl, strlen($rdata2)) . $rdata2;

        $this->assertEquals($encoded, $this->server->invokePrivateMethod('ds_encode_rr', [$decoded], 0));
        $this->assertEquals($encoded2, $this->server->invokePrivateMethod('ds_encode_rr', [$decoded2], 0));
    }

    /**
     * Tests the ds_encode_type() and ds_decode_type() methods.
     *
     * @throws \ReflectionException
     */
    public function testDs_encode_decode_type()
    {
        $decoded_1 = '192.168.0.1';
        $encoded_1 = inet_pton($decoded_1);

        $decoded_2 = '2001:acad:1337:b8::19';
        $encoded_2 = inet_pton($decoded_2);

        $decoded_3 = '192.168.1';
        $encoded_3 = str_repeat("\0", 4);

        $decoded_4 = '2001:acad:1337:b8:19';
        $encoded_4 = str_repeat("\0", 16);

        $decoded_5 = 'dns1.example.com.';
        $encoded_5 = chr(4) . 'dns1' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $decoded_6 = [
            'mname' => 'example.com.',
            'rname' => 'postmaster.example.com',
            'serial'=> 1970010188,
            'refresh' => 1800,
            'retry' => 7200,
            'expire' => 10800,
            'minimum-ttl' => 3600,
        ];

        $decoded_6_prime = [
            'mname' => 'example.com.',
            'rname' => 'postmaster.example.com.',
            'serial'=> 1970010188,
            'refresh' => 1800,
            'retry' => 7200,
            'expire' => 10800,
            'minimum' => 3600,
        ];

        $encoded_6 =
            chr(7) . 'example' . chr(3) . 'com' . "\0" .
            chr(10) . 'postmaster' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('NNNNN', 1970010188, 1800, 7200, 10800, 3600);

        $decoded_7 = 'mail.example.com.';
        $encoded_7 = pack('n', 10) . chr(4) . 'mail' . chr(7) . 'example' . chr(3) . 'com' . "\0";
        $decoded_7_prime = [
            'priority' => 10,
            'host' => 'mail.example.com.',
        ];

        $decoded_8 = 'This is a comment.';
        $encoded_8 = chr(18) . $decoded_8;

        $methodName = 'ds_encode_type';
        $this->assertEquals($encoded_1, $this->server->invokePrivateMethod($methodName,1, $decoded_1, null));
        $this->assertEquals($encoded_2, $this->server->invokePrivateMethod($methodName,28, $decoded_2, null));
        $this->assertEquals($encoded_3, $this->server->invokePrivateMethod($methodName,1, $decoded_3, null));
        $this->assertEquals($encoded_4, $this->server->invokePrivateMethod($methodName,28, $decoded_4, null));
        $this->assertEquals($encoded_5, $this->server->invokePrivateMethod($methodName,2, $decoded_5, null));
        $this->assertEquals($encoded_6, $this->server->invokePrivateMethod($methodName,6, $decoded_6, null));
        $this->assertEquals($encoded_7, $this->server->invokePrivateMethod($methodName,15, $decoded_7, null));
        $this->assertEquals($encoded_8, $this->server->invokePrivateMethod($methodName,16, $decoded_8, null));

        $methodName = 'ds_decode_type';
        $this->assertEquals($decoded_1, $this->server->invokePrivateMethod($methodName,1, $encoded_1, null)['value']);
        $this->assertEquals($decoded_2, $this->server->invokePrivateMethod($methodName,28, $encoded_2, null)['value']);
        $this->assertEquals($decoded_5, $this->server->invokePrivateMethod($methodName,2, $encoded_5, null)['value']);
        $this->assertEquals($decoded_6_prime, $this->server->invokePrivateMethod($methodName,6, $encoded_6, null)['value']);
        $this->assertEquals($decoded_7_prime, $this->server->invokePrivateMethod($methodName,15, $encoded_7, null)['value']);
        $this->assertEquals($decoded_8, $this->server->invokePrivateMethod($methodName,16, $encoded_8, null)['value']);
    }
}