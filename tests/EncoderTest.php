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

use yswery\DNS\Encoder;

class EncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeFlags()
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

        $this->assertEquals($encoded, Encoder::encodeFlags($flags));
    }
    
    public function testEncodeLabel()
    {
        $input_1 = 'www.example.com.';
        $expectation_1 = chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $input_2 = '.';
        $expectation_2 = "\0";

        $input_3 = 'tld.';
        $expectation_3 = chr(3) . 'tld' . "\0";

        $this->assertEquals($expectation_1, Encoder::encodeLabel($input_1));
        $this->assertEquals($expectation_2, Encoder::encodeLabel($input_2));
        $this->assertEquals($expectation_3, Encoder::encodeLabel($input_3));
    }
    
    public function testEncodeQuestionResourceRecord()
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

        $this->assertEquals($expectation_1, Encoder::encodeQuestionResourceRecord($input_1));
        $this->assertEquals($expectation_2, Encoder::encodeQuestionResourceRecord($input_2));
        $this->assertEquals($expectation_3, Encoder::encodeQuestionResourceRecord($input_3));
    }
    
    public function testEncodeResourceRecord()
    {
        $name = 'example.com.';
        $nameEncoded = Encoder::encodeLabel($name);
        $exchange = 'mail.example.com.';
        $exchangeEncoded = Encoder::encodeLabel($exchange);
        $priority = 10;
        $ttl = 1337;
        $class = 1; //INTERNET
        $type = 15; //MX
        $ipAddress = '192.163.5.2';

        $rdata = pack('n', $priority) . $exchangeEncoded;
        $rdata2 = inet_pton($ipAddress);

        $decoded1 = $decoded2 = [
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

        $encoded1 = $nameEncoded . pack('nnNn', $type, $class, $ttl, strlen($rdata)) . $rdata;
        $encoded2 = $nameEncoded . pack('nnNn', 1, $class, $ttl, strlen($rdata2)) . $rdata2;

        $this->assertEquals($encoded1, Encoder::encodeResourceRecord([$decoded1]));
        $this->assertEquals($encoded2, Encoder::encodeResourceRecord([$decoded2]));
    }
    
    public function testEncodeType()
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

        $encoded_6 =
            chr(7) . 'example' . chr(3) . 'com' . "\0" .
            chr(10) . 'postmaster' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('NNNNN', 1970010188, 1800, 7200, 10800, 3600);

        $decoded_7 = 'mail.example.com.';
        $encoded_7 = pack('n', 10) . chr(4) . 'mail' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $decoded_8 = 'This is a comment.';
        $encoded_8 = chr(18) . $decoded_8;

        $this->assertEquals($encoded_1, Encoder::encodeType(1, $decoded_1));
        $this->assertEquals($encoded_2, Encoder::encodeType(28, $decoded_2));
        $this->assertEquals($encoded_3, Encoder::encodeType(1, $decoded_3));
        $this->assertEquals($encoded_4, Encoder::encodeType(28, $decoded_4));
        $this->assertEquals($encoded_5, Encoder::encodeType(2, $decoded_5));
        $this->assertEquals($encoded_6, Encoder::encodeType(6, $decoded_6));
        $this->assertEquals($encoded_7, Encoder::encodeType(15, $decoded_7));
        $this->assertEquals($encoded_8, Encoder::encodeType(16, $decoded_8));
    }
}