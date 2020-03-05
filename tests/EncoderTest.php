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

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\Encoder;
use yswery\DNS\Header;
use yswery\DNS\RdataEncoder;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;

class EncoderTest extends TestCase
{
    public function testEncodeDomainName()
    {
        $input_1 = 'www.example.com.';
        $expectation_1 = chr(3).'www'.chr(7).'example'.chr(3).'com'."\0";

        $input_2 = '.';
        $expectation_2 = "\0";

        $input_3 = 'tld.';
        $expectation_3 = chr(3).'tld'."\0";

        $this->assertEquals($expectation_1, Encoder::encodeDomainName($input_1));
        $this->assertEquals($expectation_2, Encoder::encodeDomainName($input_2));
        $this->assertEquals($expectation_3, Encoder::encodeDomainName($input_3));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testEncodeQuestionResourceRecord()
    {
        $input_1 = [];
        $input_1[] = (new ResourceRecord())
            ->setName('www.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $expectation_1 =
            chr(3).'www'.chr(7).'example'.chr(3).'com'."\0".
            pack('nn', 1, 1);

        $input_2 = [];
        $input_2[] = (new ResourceRecord())
            ->setName('domain.com.au.')
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(2);

        $expectation_2 =
            chr(6).'domain'.chr(3).'com'.chr(2).'au'."\0".
            pack('nn', 15, 1);

        $input_3 = [$input_1[0], $input_2[0]];
        $expectation_3 = $expectation_1.$expectation_2;

        $this->assertEquals($expectation_1, Encoder::encodeResourceRecords($input_1));
        $this->assertEquals($expectation_2, Encoder::encodeResourceRecords($input_2));
        $this->assertEquals($expectation_3, Encoder::encodeResourceRecords($input_3));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testEncodeResourceRecord()
    {
        $name = 'example.com.';
        $nameEncoded = Encoder::encodeDomainName($name);
        $exchange = 'mail.example.com.';
        $exchangeEncoded = Encoder::encodeDomainName($exchange);
        $preference = 10;
        $ttl = 1337;
        $class = ClassEnum::INTERNET;
        $type = RecordTypeEnum::TYPE_MX;
        $ipAddress = '192.163.5.2';

        $rdata = pack('n', $preference).$exchangeEncoded;
        $rdata2 = inet_pton($ipAddress);

        $decoded1 = (new ResourceRecord())
            ->setName($name)
            ->setTtl($ttl)
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setRdata([
                'preference' => $preference,
                'exchange' => $exchange,
            ]);

        $decoded2 = (new ResourceRecord())
            ->setName($name)
            ->setTtl($ttl)
            ->setType(RecordTypeEnum::TYPE_A)
            ->setRdata($ipAddress);

        $encoded1 = $nameEncoded.pack('nnNn', $type, $class, $ttl, strlen($rdata)).$rdata;
        $encoded2 = $nameEncoded.pack('nnNn', 1, $class, $ttl, strlen($rdata2)).$rdata2;

        $this->assertEquals($encoded1, Encoder::encodeResourceRecords([$decoded1]));
        $this->assertEquals($encoded2, Encoder::encodeResourceRecords([$decoded2]));
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testEncodeType()
    {
        $decoded_1 = '192.168.0.1';
        $encoded_1 = inet_pton($decoded_1);

        $decoded_2 = '2001:acad:1337:b8::19';
        $encoded_2 = inet_pton($decoded_2);

        $decoded_5 = 'dns1.example.com.';
        $encoded_5 = chr(4).'dns1'.chr(7).'example'.chr(3).'com'."\0";

        $decoded_6 = [
            'mname' => 'example.com.',
            'rname' => 'postmaster.example.com',
            'serial' => 1970010188,
            'refresh' => 1800,
            'retry' => 7200,
            'expire' => 10800,
            'minimum' => 3600,
        ];

        $encoded_6 =
            chr(7).'example'.chr(3).'com'."\0".
            chr(10).'postmaster'.chr(7).'example'.chr(3).'com'."\0".
            pack('NNNNN', 1970010188, 1800, 7200, 10800, 3600);

        $decoded_7 = [
            'preference' => 15,
            'exchange' => 'mail.example.com.',
        ];

        $encoded_7 = pack('n', 15).chr(4).'mail'.chr(7).'example'.chr(3).'com'."\0";

        $decoded_8 = 'This is a comment.';
        $encoded_8 = chr(18).$decoded_8;

        $this->assertEquals($encoded_1, RdataEncoder::encodeRdata(1, $decoded_1));
        $this->assertEquals($encoded_2, RdataEncoder::encodeRdata(28, $decoded_2));
        $this->assertEquals($encoded_5, RdataEncoder::encodeRdata(2, $decoded_5));
        $this->assertEquals($encoded_6, RdataEncoder::encodeRdata(6, $decoded_6));
        $this->assertEquals($encoded_7, RdataEncoder::encodeRdata(15, $decoded_7));
        $this->assertEquals($encoded_8, RdataEncoder::encodeRdata(16, $decoded_8));
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testInvalidIpv4()
    {
        RdataEncoder::encodeRdata(RecordTypeEnum::TYPE_A, '192.168.1');
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testInvalidIpv6()
    {
        RdataEncoder::encodeRdata(RecordTypeEnum::TYPE_AAAA, '2001:acad:1337:b8:19');
    }

    public function testEncodeHeader()
    {
        $id = 1337;
        $flags = 0b1000010000000000;
        $qdcount = 1;
        $ancount = 2;
        $nscount = 0;
        $arcount = 0;

        $encoded = pack('nnnnnn', $id, $flags, $qdcount, $ancount, $nscount, $arcount);

        $header = new Header();
        $header
            ->setId($id)
            ->setResponse(true)
            ->setOpcode(Header::OPCODE_STANDARD_QUERY)
            ->setAuthoritative(true)
            ->setTruncated(false)
            ->setRecursionDesired(false)
            ->setRecursionAvailable(false)
            ->setRcode(Header::RCODE_NO_ERROR)
            ->setQuestionCount($qdcount)
            ->setAnswerCount($ancount)
            ->setNameServerCount($nscount)
            ->setAdditionalRecordsCount($arcount)
        ;

        $this->assertEquals($encoded, Encoder::encodeHeader($header));
    }
}
