<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Tests\Resolver;

use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\ResourceRecord;

class JsonResolverTest extends AbstractResolverTest
{
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

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testIsWildcardDomain()
    {
        $input1 = '*.example.com.';
        $input2 = '*.sub.domain.com.';
        $input3 = '*';
        $input4 = 'www.test.com.au.';

        $resolver = new JsonResolver([]);

        $this->assertTrue($resolver->isWildcardDomain($input1));
        $this->assertTrue($resolver->isWildcardDomain($input2));
        $this->assertTrue($resolver->isWildcardDomain($input3));
        $this->assertFalse($resolver->isWildcardDomain($input4));
    }
}
