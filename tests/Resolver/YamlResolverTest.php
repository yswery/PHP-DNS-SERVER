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

use Symfony\Component\Yaml\Exception\ParseException;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\YamlResolver;
use yswery\DNS\ResourceRecord;

class YamlResolverTest extends AbstractResolverTest
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/records.yml',
            __DIR__.'/../Resources/example.com.yml',
        ];
        $this->resolver = new YamlResolver($files);
    }

    /**
     * @throws \Exception
     */
    public function testParseException()
    {
        $this->expectException(ParseException::class);
        new YamlResolver([__DIR__.'/../Resources/invalid_dns_records.json']);
    }

    public function testResolveLegacyRecord()
    {
        $question[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setTtl(300)
            ->setRdata([
                'preference' => 20,
                'exchange' => 'mail-gw1.test2.com.',
            ]);

        $expectation[] = (new ResourceRecord())
            ->setName('test2.com.')
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setTtl(300)
            ->setRdata([
                'preference' => 30,
                'exchange' => 'mail-gw2.test2.com.',
            ]);

        $this->assertEquals($expectation, $this->resolver->getAnswer($question));
    }
}
