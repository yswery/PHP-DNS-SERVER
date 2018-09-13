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

use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\ResourceRecord;
use yswery\DNS\RecordTypeEnum;
use PHPUnit\Framework\TestCase;

class JsonResolverTest extends TestCase
{
    /**
     * @var JsonResolver
     */
    protected $storage;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->storage = new JsonResolver(__DIR__.'/Resources/test_records.json');
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
                'MX' => [
                    [
                        'exchange' => 'mail-gw1.test.com',
                        'preference' => 10,
                    ],
                    [
                        'exchange' => 'mail-gw2.test.com',
                        'preference' => 20,
                    ],
                ],
                'NS' => [
                    'ns1.test.com',
                    'ns2.test.com',
                ],
                'TXT' => 'Some text.',
                'AAAA' => 'DEAD:01::BEEF',
                'CNAME' => 'www2.test.com',
                'SOA' => [
                    [
                        'mname' => 'ns1.test.com',
                        'rname' => 'admin.test.com',
                        'serial' => '2014111100',
                        'retry' => '7200',
                        'refresh' => '1800',
                        'expire' => '8600',
                        'minimum' => '300',
                    ],
                ],
            ],
            'test2.com' => [
                'A' => [
                    '111.111.111.111',
                    '112.112.112.112',
                ],
                'MX' => [
                    [
                        'preference' => 20,
                        'exchange' => 'mail-gw1.test2.com.',
                    ],
                    [
                        'preference' => 30,
                        'exchange' => 'mail-gw2.test2.com.',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->storage->getRecords());
    }

    public function testHostRecordResolves()
    {
        $question[] = (new ResourceRecord())
            ->setName('test.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('111.111.111.111');

        $this->assertEquals($expectation, $this->storage->getAnswer($question));
    }

    public function testUnconfiguredRecordDoesNotResolve()
    {
        $question[] = (new ResourceRecord())
            ->setName('testestestes.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $this->assertEmpty($this->storage->getAnswer($question));
    }

    public function testHostRecordReturnsArray()
    {
        $question[] = (new ResourceRecord())
            ->setName('test2.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('111.111.111.111');

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('112.112.112.112');

        $this->assertEquals($expectation, $this->storage->getAnswer($question));
    }

    /**
     * @throws \Exception
     */
    public function testConstructorThrowsExceptions()
    {
        //Non-existent file
        $this->expectException(\Exception::class);
        new JsonResolver('blah.json');

        //Cannot parse JSON
        $this->expectException(\Exception::class);
        new JsonResolver(__DIR__.'/Resources/invalid_dns_records.json');

        //TTL is not an integer
        $this->expectException(\Exception::class);
        new JsonResolver(__DIR__.'/Resources/test_records.json', '300');
    }

    /**
     * @throws \Exception
     */
    public function testConstructorLoadsRecords()
    {
        $this->storage = new JsonResolver(__DIR__.'/Resources/test_records.json');
        $this->assertTrue(false !== $this->storage);
    }
}
