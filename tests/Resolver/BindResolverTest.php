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

use Badcow\DNS\Parser\ParseException;
use yswery\DNS\RdataEncoder;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\BindResolver;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

class BindResolverTest extends AbstractResolverTest
{
    /**
     * @throws ParseException
     */
    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/example.com.db',
            __DIR__.'/../Resources/test2.com.db',
        ];
        $this->resolver = new BindResolver($files);
    }

    /**
     * @throws ParseException
     * @throws UnsupportedTypeException
     */
    public function testResolver()
    {
        $files = [__DIR__.'/../Resources/example.com-2.db'];
        $resolver = new BindResolver($files);

        $query = new ResourceRecord();
        $query->setQuestion(true);
        $query->setType(2);
        $query->setName('example.com.');

        $nsAnswer = $resolver->getAnswer([$query]);
        $this->assertCount(2, $nsAnswer);
        $this->assertEquals('ns2.nameserver.com.', (string) $nsAnswer[1]->getRdata());
        $this->assertEquals(chr(3).'ns2'.chr(10).'nameserver'.chr(3).'com'.chr(0), RdataEncoder::encodeRdata(2, $nsAnswer[1]->getRdata()));

        $query = new ResourceRecord();
        $query->setQuestion(true);
        $query->setType(RecordTypeEnum::TYPE_AAAA);
        $query->setName('ipv6.domain.example.com.');

        $ipv6 = $resolver->getAnswer([$query]);
        $this->assertCount(1, $ipv6);
        $this->assertEquals('0000:0000:0000:0000:0000:0000:0000:0001', (string) $ipv6[0]->getRdata());
        $this->assertEquals(inet_pton('::1'), RdataEncoder::encodeRdata(28, $ipv6[0]->getRdata()));
    }
}
