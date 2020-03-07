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

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\ResolverInterface;
use yswery\DNS\ResourceRecord;

abstract class AbstractResolverTest extends TestCase
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    public function testGetAnswer()
    {
        $query[] = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_SOA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $query[] = (new ResourceRecord())
            ->setName('example.com.')
            ->setType(RecordTypeEnum::TYPE_AAAA)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $answer = $this->resolver->getAnswer($query);
        $this->assertCount(2, $answer);
        list($soa, $aaaa) = $answer;

        $this->assertEquals('example.com.', $soa->getName());
        $this->assertEquals(ClassEnum::INTERNET, $soa->getClass());
        $this->assertEquals(10800, $soa->getTtl());
        $this->assertEquals(RecordTypeEnum::TYPE_SOA, $soa->getType());
        $this->assertEquals('example.com.', $soa->getRdata()['mname']);
        $this->assertEquals('postmaster.example.com.', $soa->getRdata()['rname']);
        $this->assertEquals(2, $soa->getRdata()['serial']);
        $this->assertEquals(3600, $soa->getRdata()['refresh']);
        $this->assertEquals(7200, $soa->getRdata()['retry']);
        $this->assertEquals(10800, $soa->getRdata()['expire']);
        $this->assertEquals(3600, $soa->getRdata()['minimum']);

        $this->assertEquals('example.com.', $aaaa->getName());
        $this->assertEquals(ClassEnum::INTERNET, $aaaa->getClass());
        $this->assertEquals(7200, $aaaa->getTtl());
        $this->assertEquals(RecordTypeEnum::TYPE_AAAA, $aaaa->getType());
        $this->assertEquals(inet_pton('2001:acad:ad::32'), inet_pton($aaaa->getRdata()));
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

        $answer = $this->resolver->getAnswer($question);
        $this->assertCount(2, $answer);
        $this->assertEquals('test2.com.', $answer[0]->getName());
        $this->assertEquals(RecordTypeEnum::TYPE_A, $answer[0]->getType());
        $this->assertEquals('111.111.111.111', (string) $answer[0]->getRdata());
        $this->assertEquals(300, $answer[0]->getTtl());
        $this->assertEquals('test2.com.', $answer[1]->getName());
        $this->assertEquals(RecordTypeEnum::TYPE_A, $answer[1]->getType());
        $this->assertEquals('112.112.112.112', (string) $answer[1]->getRdata());
        $this->assertEquals(300, $answer[1]->getTtl());
    }

    public function testWildcardDomains()
    {
        $question[] = (new ResourceRecord())
            ->setName('badcow.subdomain.example.com.')
            ->setType(RecordTypeEnum::TYPE_A)
            ->setQuestion(true);

        $answer = $this->resolver->getAnswer($question);
        $this->assertCount(1, $answer);
        $this->assertEquals('badcow.subdomain.example.com.', $answer[0]->getName());
        $this->assertEquals(1, $answer[0]->getType());
        $this->assertEquals('192.168.1.42', (string) $answer[0]->getRdata());
        $this->assertEquals(7200, $answer[0]->getTtl());
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testIsWildcardDomain()
    {
        $resolver = new JsonResolver([]);
        $this->assertTrue($resolver->isWildcardDomain('*.cat.com.'));
        $this->assertFalse($resolver->isWildcardDomain('github.com.'));
    }

    public function testAllowsRecursion()
    {
        $this->assertFalse($this->resolver->allowsRecursion());
    }

    public function testIsAuthority()
    {
        $this->assertTrue($this->resolver->isAuthority('example.com.'));
    }

    public function testSrvRdata()
    {
        $query[] = (new ResourceRecord())
            ->setName('_ldap._tcp.example.com.')
            ->setType(RecordTypeEnum::TYPE_SRV)
            ->setQuestion(true);

        $expectation[] = (new ResourceRecord())
            ->setName('_ldap._tcp.example.com.')
            ->setType(RecordTypeEnum::TYPE_SRV)
            ->setTtl(7200)
            ->setRdata([
                'priority' => 1,
                'weight' => 5,
                'port' => 389,
                'target' => 'ldap.example.com.',
            ]);

        $answer = $this->resolver->getAnswer($query);
        $this->assertCount(1, $answer);

        $srv = $answer[0];
        $this->assertEquals('_ldap._tcp.example.com.', $srv->getName());
        $this->assertEquals(ClassEnum::INTERNET, $srv->getClass());
        $this->assertEquals(7200, $srv->getTtl());
        $this->assertEquals(RecordTypeEnum::TYPE_SRV, $srv->getType());

        $this->assertEquals(1, $srv->getRdata()['priority']);
        $this->assertEquals(5, $srv->getRdata()['weight']);
        $this->assertEquals(389, $srv->getRdata()['port']);
        $this->assertEquals('ldap.example.com.', $srv->getRdata()['target']);
    }
}
