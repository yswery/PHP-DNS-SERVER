<?php

namespace yswery\DNS\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\SystemResolver;
use yswery\DNS\ResourceRecord;

class SystemResolverTest extends TestCase
{
    public function testGetAnswer()
    {
        $query1 = (new ResourceRecord())
            ->setQuestion(true)
            ->setName('google-public-dns-a.google.com.')
            ->setType(RecordTypeEnum::TYPE_A);

        $expectation1 = '8.8.8.8';

        $query2 = (new ResourceRecord())
            ->setQuestion(true)
            ->setName('google-public-dns-a.google.com.')
            ->setType(RecordTypeEnum::TYPE_AAAA);

        $expectation2 = '2001:4860:4860::8888';

        $query3 = (new ResourceRecord())
            ->setQuestion(true)
            ->setName('google-public-dns-b.google.com.')
            ->setType(RecordTypeEnum::TYPE_A);

        $expectation3 = '8.8.4.4';

        $query4 = (new ResourceRecord())
            ->setQuestion(true)
            ->setName('google-public-dns-b.google.com.')
            ->setType(RecordTypeEnum::TYPE_AAAA);

        $expectation4 = '2001:4860:4860::8844';

        $resolver = new SystemResolver();

        $this->assertEquals($expectation1, $resolver->getAnswer([$query1])[0]->getRdata());
        $this->assertEquals($expectation2, $resolver->getAnswer([$query2])[0]->getRdata());
        $this->assertEquals($expectation3, $resolver->getAnswer([$query3])[0]->getRdata());
        $this->assertEquals($expectation4, $resolver->getAnswer([$query4])[0]->getRdata());
    }
}
