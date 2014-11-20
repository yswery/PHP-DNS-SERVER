<?php

class JsonStorageProviderTest extends PHPUnit_Framework_TestCase {
    
    /**
     * @var yswery\DNS\JsonStorageProvider
     */
    protected $storage;
    
    public function setUp()
    {
        $this->storage = new \yswery\DNS\JsonStorageProvider(__DIR__ . '/test_records.json');
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
    
}
