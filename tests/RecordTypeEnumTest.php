<?php

class RecordTypeEnumTest extends PHPUnit_Framework_TestCase {

    /**
     * @var yswery\DNS\RecordTypeEnum
     */
    protected $recordTypes;

    public function setUp()
    {
        $this->recordTypes = new \yswery\DNS\RecordTypeEnum;
    }

    public function testGetsHostRecordIndex()
    {
        $hostIndex = $this->recordTypes->get_type_index('A');
        $this->assertTrue($hostIndex === \yswery\DNS\RecordTypeEnum::TYPE_A);
    }

    public function testDoesNotGetInvalidRecordTypeIndex()
    {
        $hostIndex = $this->recordTypes->get_type_index('BLAH');
        $this->assertTrue($hostIndex === false);
    }

    public function testGetsNameFromType()
    {
        $typeName = $this->recordTypes->get_name(\yswery\DNS\RecordTypeEnum::TYPE_A);
        $this->assertTrue('A' === $typeName);
    }

    public function testDoesNotGetInvalidNameFromType()
    {
        $typeName = $this->recordTypes->get_name(932);
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
        $this->assertTrue($this->recordTypes->get_types() === $expected);
    }

}