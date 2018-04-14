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

use yswery\DNS\RecordTypeEnum;

/**
 * Class RecordTypeEnumTest
 */
class RecordTypeEnumTest extends \PHPUnit_Framework_TestCase {

    public function testGetsHostRecordIndex()
    {
        $hostIndex = RecordTypeEnum::getTypeIndex('A');
        $this->assertTrue($hostIndex === RecordTypeEnum::TYPE_A);
    }

    public function testDoesNotGetInvalidRecordTypeIndex()
    {
        $hostIndex = RecordTypeEnum::getTypeIndex('BLAH');
        $this->assertTrue($hostIndex === false);
    }

    public function testGetsNameFromType()
    {
        $typeName = RecordTypeEnum::getName(RecordTypeEnum::TYPE_A);
        $this->assertTrue('A' === $typeName);
    }

    public function testDoesNotGetInvalidNameFromType()
    {
        $typeName = RecordTypeEnum::getName(932);
        $this->assertTrue(false === $typeName);
    }

    public function testGetTypes()
    {
        $expected = [
            'A' => 1,
            'NS' => 2,
            'CNAME' => 5,
            'SOA' => 6,
            'PTR' => 12,
            'MX' => 15,
            'TXT' => 16,
            'AAAA' => 28,
            'OPT' => 41,
            'AXFR' => 252,
            'ANY' => 255,
            'AFSDB' => 18,
            'APL' => 42,
            'CAA' => 257,
            'CDNSKEY' => 60,
            'CDS' => 59,
            'CERT' => 37,
            'DHCID' => 49,
            'DLV' => 32769,
            'DNSKEY' => 48,
            'DS' => 43,
            'IPSECKEY' => 45,
            'KEY' => 25,
            'KX' => 36,
            'LOC' => 29,
            'NAPTR' => 35,
            'NSEC' => 47,
            'NSEC3' => 50,
            'NSEC3PARAM' => 51,
            'RRSIG' => 46,
            'RP' => 17,
            'SIG' => 24,
            'SRV' => 33,
            'SSHFP' => 44,
            'TA' => 32768,
            'TKEY' => 249,
            'TLSA' => 52,
            'TSIG' => 250,
            'URI' => 256,
            'DNAME' => 39,
        ];

        $this->assertTrue(RecordTypeEnum::getTypes() === $expected);
    }
}