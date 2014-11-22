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
        );
        $this->assertTrue(\yswery\DNS\RecordTypeEnum::get_types() === $expected);
    }

}