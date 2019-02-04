<?php

namespace yswery\DNS\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\GoogleDnsResolver;
use yswery\DNS\ResourceRecord;

class GoogleDnsResolveTest extends TestCase
{
    private const EXAMPLE_QUERY = 'apple.com.';

    /**
     * @var GoogleDnsResolver
     */
    protected $resolver;

    /**
     * @var array
     */
    protected $successResponse;

    /**
     * @var array
     */
    protected $failureResponse;

    public function setUp()
    {
        $this->resolver = new GoogleDnsResolver(300);

        $this->failureResponse = json_decode(
            file_get_contents(__DIR__.'/../Resources/google-dns-query-failure.json'),
            true
        );
        $this->successResponse = json_decode(
            file_get_contents(__DIR__.'/../Resources/google-dns-query-success.json'),
            true
        );
    }

    public function testRecordResolve(): void
    {
        $query = (new ResourceRecord())
            ->setName(self::EXAMPLE_QUERY)
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $answers = $this->resolver->createAnswer($query, $this->successResponse);

        static::assertArrayHasKey(0, $answers);

        $answer = $answers[0];

        static::assertEquals('apple.com.', $answer->getName());
        static::assertEquals(RecordTypeEnum::TYPE_A, $answer->getType());
        static::assertEquals(3599, $answer->getTtl());
        static::assertEquals('17.178.96.59', $answer->getRdata());
    }

    public function testRecordFailedToResolve() {
        $query = (new ResourceRecord())
            ->setName(self::EXAMPLE_QUERY)
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $answers = $this->resolver->createAnswer($query, $this->failureResponse);

        static::assertArrayHasKey(0, $answers);

        $answer = $answers[0];

        static::assertEquals(self::EXAMPLE_QUERY, $answer->getName());
        static::assertEquals(RecordTypeEnum::TYPE_A, $answer->getType());
        static::assertEquals(300, $answer->getTtl());
        static::assertEquals(null, $answer->getRdata());
    }
}
