<?php

namespace yswery\DNS\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\ResourceRecord;

class JsonResolverTest extends TestCase
{
    /**
     * @var JsonResolver
     */
    protected $resolver;

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/example.com.json',
            __DIR__.'/../Resources/test_records.json',
        ];
        $this->resolver = new JsonResolver($files, 300);
    }

    public function testGetAnswer()
    {
        $soa = (new ResourceRecord())
            ->setName('example.com.')
            ->setClass(ClassEnum::INTERNET)
            ->setTtl(10800)
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setRdata([
                'mname' => 'example.com.',
                'rname' => 'postmaster.example.com.',
                'serial' => 2,
                'refresh' => 3600,
                'retry' => 7200,
                'expire' => 10800,
                'minimum' => 3600,
            ]);

        $aaaa = (new ResourceRecord())
            ->setName('example.com.')
            ->setClass(ClassEnum::INTERNET)
            ->setTtl(7200)
            ->setType(RecordTypeEnum::TYPE_AAAA)
            ->setRdata('2001:acad:ad::32');

        $soa_query = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $aaaa_query = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_AAAA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $query = [$soa_query, $aaaa_query];
        $answer = [$soa, $aaaa];

        $this->assertEquals($answer, $this->resolver->getAnswer($query));
    }

    public function testResolveLegacyRecord()
    {
        $question[] = (new ResourceRecord())
            ->setName('test.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('111.111.111.111');

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }

    public function testUnconfiguredRecordDoesNotResolve()
    {
        $question[] = (new ResourceRecord())
            ->setName('testestestes.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $this->assertEmpty($this->resolver->getAnswer($question));
    }

    public function testHostRecordReturnsArray()
    {
        $question[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('111.111.111.111');

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(300)
            ->setRdata('112.112.112.112');

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }

    public function testIsWildcardDomain()
    {
        $input1 = '*.example.com.';
        $input2 = '*.sub.domain.com.';
        $input3 = '*';
        $input4 = 'www.test.com.au.';

        $this->assertTrue($this->resolver->isWildcardDomain($input1));
        $this->assertTrue($this->resolver->isWildcardDomain($input2));
        $this->assertTrue($this->resolver->isWildcardDomain($input3));
        $this->assertFalse($this->resolver->isWildcardDomain($input4));
    }

    public function testWildcardDomains()
    {
        $question[] = (new ResourceRecord())
            ->setName('badcow.subdomain.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('badcow.subdomain.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setTtl(7200)
            ->setRdata('192.168.1.42');

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }

    public function testAllowsRecursion()
    {
        $this->assertFalse($this->resolver->allowsRecursion());
    }

    public function testIsAuthority()
    {
        $this->assertTrue($this->resolver->isAuthority('example.com.'));
    }
}
