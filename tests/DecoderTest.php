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

use yswery\DNS\Decoder;
use yswery\DNS\Encoder;
use yswery\DNS\RecordTypeEnum;

class DecoderTest extends \PHPUnit_Framework_TestCase
{
    public function testDecodeFlags()
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
        
        $this->assertEquals($flags, Decoder::decodeFlags($encoded));
    }
    
    public function testDecodeLabel()
    {
        $decoded_1 = 'www.example.com.';
        $encoded_1 = chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0";

        $decoded_2 = '.';
        $encoded_2 = "\0";

        $decoded_3 = 'tld.';
        $encoded_3 = chr(3) . 'tld' . "\0";

        $offset = 0;
        $this->assertEquals($decoded_1, Decoder::decodeLabel($encoded_1, $offset));

        $offset = 0;
        $this->assertEquals($decoded_2, Decoder::decodeLabel($encoded_2, $offset));

        $offset = 0;
        $this->assertEquals($decoded_3, Decoder::decodeLabel($encoded_3, $offset));
    }

    public function testDecodeQuestionResourceRecord()
    {
        $decoded_1 = [[
            'qname' => 'www.example.com.',
            'qtype' => RecordTypeEnum::TYPE_A,
            'qclass' => 1, //IN
        ]];

        $encoded_1 =
            chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('nn', 1, 1);

        $decoded_2 = [[
            'qname' => 'domain.com.au.',
            'qtype' => RecordTypeEnum::TYPE_MX,
            'qclass' => 1, //IN
        ]];

        $encoded_2 =
            chr(6) . 'domain' . chr(3) . 'com' . chr(2) . 'au' . "\0" .
            pack('nn', 15, 1);

        $decoded_3 = [$decoded_1[0], $decoded_2[0]];
        $encoded_3 = $encoded_1 . $encoded_2;

        $offset = 0;
        $this->assertEquals($decoded_1, Decoder::decodeQuestionResourceRecord($encoded_1, $offset, 1));
        $offset = 0;
        $this->assertEquals($decoded_2, Decoder::decodeQuestionResourceRecord($encoded_2, $offset, 1));
        $offset = 0;
        $this->assertEquals($decoded_3, Decoder::decodeQuestionResourceRecord($encoded_3, $offset, 2));
    }
    
    public function testDecodeResourceRecord()
    {
        $name = 'example.com.';
        $nameEncoded = Encoder::encodeLabel($name);
        $exchange = 'mail.example.com.';
        $exchangeEncoded = Encoder::encodeLabel($exchange);
        $priority = 10;
        $ttl = 1337;
        $class = 1; //INTERNET
        $type = RecordTypeEnum::TYPE_MX;
        $ipAddress = '192.163.5.2';

        $rdata = pack('n', $priority) . $exchangeEncoded;
        $rdata2 = inet_pton($ipAddress);

        $decoded1 = [
            'name' => $name,
            'class' => $class,
            'ttl' => $ttl,
            'type' => $type,
            'data' => [
                'value' => [
                    'priority' => $priority,
                    'host' => $exchange,
                ],
            ],
            'dlength' => 20,
        ];

        $decoded2 = [
            'name' => $name,
            'class' => $class,
            'ttl' => $ttl,
            'type' => RecordTypeEnum::TYPE_A,
            'data' => [
                'value' => $ipAddress,
            ],
            'dlength' => 4,
        ];

        $encoded1 = $nameEncoded . pack('nnNn', $type, $class, $ttl, strlen($rdata)) . $rdata;
        $encoded2 = $nameEncoded . pack('nnNn', 1, $class, $ttl, strlen($rdata2)) . $rdata2;

        $offset = 0;
        $this->assertEquals([$decoded1], Decoder::decodeResourceRecord($encoded1, $offset, 1));

        $offset = 0;
        $this->assertEquals([$decoded2], Decoder::decodeResourceRecord($encoded2, $offset, 1));
    }

    public function testDecodeType()
    {
        $decoded_1 = '192.168.0.1';
        $encoded_1 = inet_pton($decoded_1);

        $decoded_2 = '2001:acad:1337:b8::19';
        $encoded_2 = inet_pton($decoded_2);

        $decoded_5 = 'dns1.example.com.';
        $encoded_5 = chr(4) . 'dns1' . chr(7) . 'example' . chr(3) . 'com' . "\0";

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

        $encoded_7 = pack('n', 10) . chr(4) . 'mail' . chr(7) . 'example' . chr(3) . 'com' . "\0";
        $decoded_7_prime = [
            'priority' => 10,
            'host' => 'mail.example.com.',
        ];

        $decoded_8 = 'This is a comment.';
        $encoded_8 = chr(strlen($decoded_8)) . $decoded_8;

        $this->assertEquals($decoded_1, Decoder::decodeType(RecordTypeEnum::TYPE_A, $encoded_1)['value']);
        $this->assertEquals($decoded_2, Decoder::decodeType(RecordTypeEnum::TYPE_AAAA, $encoded_2)['value']);
        $this->assertEquals($decoded_5, Decoder::decodeType(RecordTypeEnum::TYPE_NS, $encoded_5)['value']);
        $this->assertEquals($decoded_6_prime, Decoder::decodeType(RecordTypeEnum::TYPE_SOA, $encoded_6)['value']);
        $this->assertEquals($decoded_7_prime, Decoder::decodeType(RecordTypeEnum::TYPE_MX, $encoded_7)['value']);
        $this->assertEquals($decoded_8, Decoder::decodeType(RecordTypeEnum::TYPE_TXT, $encoded_8)['value']);
    }
}