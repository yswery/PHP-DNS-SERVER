<?php

class RecordTypeEnumTest extends PHPUnit_Framework_TestCase {

    public function testGetsHostRecordIndex()
    {
        $hostIndex = \yswery\DNS\RecordTypeEnum::get_type_index('A');
        $this->assertTrue($hostIndex === \yswery\DNS\RecordTypeEnum::TYPE_A);
    }

    public function testDoesNotGetInvalidRecordTypeIndex()
    {
        $hostIndex = \yswery\DNS\RecordTypeEnum::get_type_index('BLAH');
        $this->assertTrue($hostIndex === false);
    }

    public function testGetsNameFromType()
    {
        $typeName = \yswery\DNS\RecordTypeEnum::get_name(\yswery\DNS\RecordTypeEnum::TYPE_A);
        $this->assertTrue('A' === $typeName);
    }

    public function testDoesNotGetInvalidNameFromType()
    {
        $typeName = \yswery\DNS\RecordTypeEnum::get_name(932);
        $this->assertTrue(false === $typeName);
    }

    public function testGetTypes()
    {
        $expected = array(
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
        );

        $this->assertTrue(\yswery\DNS\RecordTypeEnum::get_types() === $expected);
    }
}