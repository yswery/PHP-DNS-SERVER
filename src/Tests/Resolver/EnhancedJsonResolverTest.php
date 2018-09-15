<?php

namespace yswery\DNS\Tests\Resolver;


use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\EnhancedJsonResolver;
use yswery\DNS\ResourceRecord;

class EnhancedJsonResolverTest extends TestCase
{
    /**
     * @var EnhancedJsonResolver
     */
    private $resolver;

    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/example.com.json',
            __DIR__.'/../Resources/test_records.json',
        ];
        $this->resolver = new EnhancedJsonResolver($files, 300);
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

        $soa_query = (new ResourceRecord)
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $aaaa_query = (new ResourceRecord)
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
}