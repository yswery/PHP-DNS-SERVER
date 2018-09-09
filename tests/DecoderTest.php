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
use yswery\DNS\ResourceRecord;

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
        $decoded_1[] = (new ResourceRecord)
            ->setName('www.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $encoded_1 =
            chr(3) . 'www' . chr(7) . 'example' . chr(3) . 'com' . "\0" .
            pack('nn', 1, 1);

        $decoded_2[] = (new ResourceRecord)
            ->setName('domain.com.au.')
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setQuestion(true);

        $encoded_2 =
            chr(6) . 'domain' . chr(3) . 'com' . chr(2) . 'au' . "\0" .
            pack('nn', 15, 1);

        $decoded_3 = [$decoded_1[0], $decoded_2[0]];
        $encoded_3 = $encoded_1 . $encoded_2;

        $offset = 0;
        $this->assertEquals($decoded_1, Decoder::decodeResourceRecords($encoded_1, $offset, 1, true));
        $offset = 0;
        $this->assertEquals($decoded_2, Decoder::decodeResourceRecords($encoded_2, $offset, 1, true));
        $offset = 0;
        $this->assertEquals($decoded_3, Decoder::decodeResourceRecords($encoded_3, $offset, 2, true));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testDecodeResourceRecords()
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

        $decoded1[]  = (new ResourceRecord())
            ->setName($name)
            ->setClass($class)
            ->setTtl($ttl)
            ->setType($type)
            ->setRdata([
                'preference' => $priority,
                'exchange' => $exchange,
            ]);

        $decoded2[]  = (new ResourceRecord())
            ->setName($name)
            ->setClass($class)
            ->setTtl($ttl)
            ->setType(RecordTypeEnum::TYPE_A)
            ->setRdata($ipAddress);

        $encoded1 = $nameEncoded . pack('nnNn', $type, $class, $ttl, strlen($rdata)) . $rdata;
        $encoded2 = $nameEncoded . pack('nnNn', 1, $class, $ttl, strlen($rdata2)) . $rdata2;

        $offset = 0;
        $this->assertEquals($decoded1, Decoder::decodeResourceRecords($encoded1, $offset, 1));

        $offset = 0;
        $this->assertEquals($decoded2, Decoder::decodeResourceRecords($encoded2, $offset, 1));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
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
            'preference' => 10,
            'exchange' => 'mail.example.com.',
        ];

        $decoded_8 = 'This is a comment.';
        $encoded_8 = chr(strlen($decoded_8)) . $decoded_8;

        $this->assertEquals($decoded_1, Decoder::decodeType(RecordTypeEnum::TYPE_A, $encoded_1));
        $this->assertEquals($decoded_2, Decoder::decodeType(RecordTypeEnum::TYPE_AAAA, $encoded_2));
        $this->assertEquals($decoded_5, Decoder::decodeType(RecordTypeEnum::TYPE_NS, $encoded_5));
        $this->assertEquals($decoded_6_prime, Decoder::decodeType(RecordTypeEnum::TYPE_SOA, $encoded_6));
        $this->assertEquals($decoded_7_prime, Decoder::decodeType(RecordTypeEnum::TYPE_MX, $encoded_7));
        $this->assertEquals($decoded_8, Decoder::decodeType(RecordTypeEnum::TYPE_TXT, $encoded_8));
    }

    public function testDecodeHeader()
    {
        $id = 1337;
        $flags = 0b1000010000000000;
        $qdcount = 1;
        $ancount = 2;
        $nscount = 0;
        $arcount = 0;

        $encoded = pack('nnnnnn', $id, $flags, $qdcount, $ancount, $nscount, $arcount);
        $header = Decoder::decodeHeader($encoded);

        $this->assertEquals($id, $header->getId());
        $this->assertEquals($qdcount, $header->getQuestionCount());
        $this->assertEquals($ancount, $header->getAnswerCount());
        $this->assertEquals($nscount, $header->getNameServerCount());
        $this->assertEquals($arcount, $header->getAdditionalRecordsCount());

        $this->assertTrue($header->isResponse());
        $this->assertEquals(0, $header->getOpcode());
        $this->assertTrue($header->isAuthoritative());
        $this->assertFalse($header->isTruncated());
        $this->assertFalse($header->isRecursionDesired());
        $this->assertFalse($header->isRecursionAvailable());
        $this->assertEquals(0, $header->getZ());
        $this->assertEquals(0, $header->getRcode());
    }
}