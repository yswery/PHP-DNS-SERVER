<?php

class JsonStorageProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var yswery\DNS\JsonStorageProvider
     */
    protected $storage;
    
    public function setUp()
    {
        $this->storage = new \yswery\DNS\JsonStorageProvider(__DIR__ . '/test_records.json');
    }

    /**
     * Tests that the constructor reads the JSON
     * in a predictable and consistent way.
     */
    public function testGetDnsRecords()
    {
        $expected = array(
            'test.com' => array(
                'A' => '111.111.111.111',
                'MX' => '112.112.112.112',
                'NS' => 'ns1.test.com',
                'TXT' => 'Some text.',
                'AAAA' => 'DEAD:01::BEEF',
            ),
            'test2.com' => array(
                'A' => array(
                    '111.111.111.111',
                    '112.112.112.112',
                )
            ),
        );

        $this->assertEquals($expected, $this->storage->getDnsRecords());
    }
    
    public function testHostRecordResolves()
    {
        $question = array(array(
            'qname' => 'test.com',
            'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
            'qclass' => 1,
        ));
        $expected = array(array(
            'name' => 'test.com',
            'class' => 1,
            'ttl' => 300,
            'data' => array(
                'type' => 1,
                'value' => '111.111.111.111',
            ),
        ));
        $answer = $this->storage->get_answer($question);
        $this->assertTrue($answer === $expected);
    }

    public function testUnconfiguredRecordDoesNotResolve()
    {
        $question = array(array(
            'qname' => 'testestestes.com',
            'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
            'qclass' => 1,
        ));
        $answer = $this->storage->get_answer($question);
        $this->assertTrue($answer === array());
    }

    public function testHostRecordReturnsArray()
    {
        $question = array(array(
            'qname' => 'test2.com',
            'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
            'qclass' => 1,
        ));
        $expected = array(
            array(
                'name' => 'test2.com',
                'class' => 1,
                'ttl' => 300,
                'data' => array(
                    'type' => 1,
                    'value' => '111.111.111.111',
                ),
            ),
            array(
                'name' => 'test2.com',
                'class' => 1,
                'ttl' => 300,
                'data' => array(
                    'type' => 1,
                    'value' => '112.112.112.112',
                ),
            ),
        );
        $answer = $this->storage->get_answer($question);
        $this->assertTrue($answer === $expected);
    }

    public function testConstructorThrowsExceptions()
    {
        $this->setExpectedException('Exception', 'Unable to open dns record file.');
        $jsonAdapter = new \yswery\DNS\JsonStorageProvider('blah.json');
        $this->setExpectedException('Exception', 'Unable to parse dns record file.');
        $jsonAdapter = new \yswery\DNS\JsonStorageProvider('invalid_dns_records.json');
    }

    public function testConstructorLoadsRecords()
    {
        $this->storage = new \yswery\DNS\JsonStorageProvider(__DIR__ . '/test_records.json');
        $this->assertTrue($this->storage !== false);
    }
    
}
