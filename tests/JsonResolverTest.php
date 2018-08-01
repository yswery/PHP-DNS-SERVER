<?php

use yswery\DNS\JsonResolver;

class JsonResolverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var yswery\DNS\JsonResolver
     */
    protected $storage;

    public function setUp()
    {
        $this->storage = new JsonResolver(__DIR__.'/test_records.json');
    }

    /**
     * Tests that the constructor reads the JSON
     * in a predictable and consistent way.
     */
    public function testGetDnsRecords()
    {
        $expected = [
            'test.com' => [
                'A' => '111.111.111.111',
                'MX' => '112.112.112.112',
                'NS' => 'ns1.test.com',
                'TXT' => 'Some text.',
                'AAAA' => 'DEAD:01::BEEF',
            ],
            'test2.com' => [
                'A' => [
                    '111.111.111.111',
                    '112.112.112.112',
                ],
                'MX' => [
                    'priority' => 25,
                    'target' => 'mail-gw1.test2.com.',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->storage->getRecords());
    }

    public function testHostRecordResolves()
    {
        $question = [
            [
                'qname' => 'test.com',
                'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
                'qclass' => 1,
            ],
        ];
        $expected = [
            [
                'name' => 'test.com',
                'class' => 1,
                'ttl' => 300,
                'data' => [
                    'type' => 1,
                    'value' => '111.111.111.111',
                ],
            ],
        ];
        $answer = $this->storage->getAnswer($question);
        $this->assertTrue($answer === $expected);
    }

    public function testUnconfiguredRecordDoesNotResolve()
    {
        $question = [
            [
                'qname' => 'testestestes.com',
                'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
                'qclass' => 1,
            ],
        ];
        $answer = $this->storage->getAnswer($question);
        $this->assertTrue($answer === []);
    }

    public function testHostRecordReturnsArray()
    {
        $question = [
            [
                'qname' => 'test2.com',
                'qtype' => \yswery\DNS\RecordTypeEnum::TYPE_A,
                'qclass' => 1,
            ],
        ];
        $expected = [
            [
                'name' => 'test2.com',
                'class' => 1,
                'ttl' => 300,
                'data' => [
                    'type' => 1,
                    'value' => '111.111.111.111',
                ],
            ],
            [
                'name' => 'test2.com',
                'class' => 1,
                'ttl' => 300,
                'data' => [
                    'type' => 1,
                    'value' => '112.112.112.112',
                ],
            ],
        ];
        $answer = $this->storage->getAnswer($question);
        $this->assertTrue($answer === $expected);
    }

    public function testConstructorThrowsExceptions()
    {
        $this->setExpectedException('\Exception', 'The file "blah.json" does not exist.');
        $jsonAdapter = new JsonResolver('blah.json');

        $this->setExpectedException('\Exception', 'Unable to parse JSON file: "invalid_dns_records.json".');
        $jsonAdapter = new JsonResolver('invalid_dns_records.json');

        $this->setExpectedException('\InvalidArgumentException', 'Default TTL must be an integer.');
        $jsonAdapter = new JsonResolver(__DIR__.'test_records.json', '300');
    }

    public function testConstructorLoadsRecords()
    {
        $this->storage = new JsonResolver(__DIR__.'/test_records.json');
        $this->assertTrue($this->storage !== false);
    }

}
