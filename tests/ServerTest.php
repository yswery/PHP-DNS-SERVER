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

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use yswery\DNS\ClassEnum;
use yswery\DNS\Decoder;
use yswery\DNS\Encoder;
use yswery\DNS\Header;
use yswery\DNS\Message;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\StackableResolver;
use yswery\DNS\Resolver\XmlResolver;
use yswery\DNS\ResourceRecord;
use yswery\DNS\Server;

class ServerTest extends TestCase
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $xmlResolver = new XmlResolver([
            __DIR__.'/Resources/test.com.xml',
        ]);

        $jsonResolver = new JsonResolver([
            __DIR__.'/Resources/test_records.json',
            __DIR__.'/Resources/example.com.json',
        ]);

        $resolver = new StackableResolver([
            $jsonResolver,
            $xmlResolver,
        ]);

        $this->server = new Server($resolver, new EventDispatcher(), null, null, false);
    }

    /**
     * @param $name
     * @param $type
     * @param $id
     *
     * @return array
     */
    private function encodeQuery($name, $type, $id)
    {
        $qname = Encoder::encodeDomainName($name);
        $flags = 0b0000000000000000;
        $header = pack('nnnnnn', $id, $flags, 1, 0, 0, 0);
        $question = $qname.pack('nn', $type, 1);

        return [$header, $question];
    }

    /**
     * Create a mock query and response pair.
     *
     * @return array
     */
    private function mockQueryAndResponse(): array
    {
        list($queryHeader, $question) = $this->encodeQuery($name = 'test.com.', RecordTypeEnum::TYPE_A, $id = 1337);
        $query = $queryHeader.$question;

        $flags = 0b1000010000000000;
        $qname = Encoder::encodeDomainName($name);
        $header = pack('nnnnnn', $id, $flags, 1, 1, 0, 0);

        $rdata = inet_pton('111.111.111.111');
        $answer = $qname.pack('nnNn', 1, 1, 300, strlen($rdata)).$rdata;

        $response = $header.$question.$answer;

        return [$query, $response];
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testHandleQueryFromStream()
    {
        list($query, $response) = $this->mockQueryAndResponse();

        $this->assertEquals($response, $this->server->handleQueryFromStream($query));
    }

    public function testStatusQueryWithNoQuestionsResolves()
    {
        $message = new Message();
        $message->getHeader()
            ->setOpcode(Header::OPCODE_STATUS_REQUEST)
            ->setId(1234);

        $encodedMessage = Encoder::encodeMessage($message);

        $message->getHeader()->setResponse(true);
        $expectation = Encoder::encodeMessage($message);

        $this->assertEquals($expectation, $this->server->handleQueryFromStream($encodedMessage));
    }

    /**
     * Tests that the server sends back a "Not implemented" RCODE for a type that has not been implemented, namely "OPT".
     *
     * @throws \yswery\DNS\UnsupportedTypeException | \Exception
     */
    public function testOptType()
    {
        $q_RR = (new ResourceRecord())
            ->setName('test.com.')
            ->setType(RecordTypeEnum::TYPE_OPT)
            ->setClass(ClassEnum::INTERNET)
            ->setQuestion(true);

        $query = new Message();
        $query->setQuestions([$q_RR])
            ->getHeader()
                ->setQuery(true)
                ->setId($id = 1337);

        $response = new Message();
        $response->setQuestions([$q_RR])
            ->getHeader()
                ->setId($id)
                ->setResponse(true)
                ->setRcode(Header::RCODE_NOT_IMPLEMENTED)
                ->setAuthoritative(true);

        $queryEncoded = Encoder::encodeMessage($query);
        $responseEncoded = Encoder::encodeMessage($response);

        $server = new Server(new DummyResolver(), new EventDispatcher());
        $this->assertEquals($responseEncoded, $server->handleQueryFromStream($queryEncoded));
    }

    public function testOnMessage()
    {
        list($query, $response) = $this->mockQueryAndResponse();
        $this->server->onMessage($query, '127.0.0.1', $socket = new MockSocket());

        $this->assertEquals($response, $socket->getLastTransmission());
    }

    /**
     * Certain queries such as SRV, SOA, and NS records SHOULD return additional records in order to prevent
     * unnecessary additional requests.
     *
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testSrvAdditionalRecords()
    {
        $queryHeader = (new Header())
            ->setQuery(true)
            ->setOpcode(Header::OPCODE_STANDARD_QUERY)
            ->setId(1234);

        $queryRecord = (new ResourceRecord())
            ->setQuestion(true)
            ->setName('_ldap._tcp.example.com.')
            ->setType(RecordTypeEnum::TYPE_SRV);

        $message = (new Message())
            ->setHeader($queryHeader)
            ->addQuestion($queryRecord);

        $query = Encoder::encodeMessage($message);
        $this->server->onMessage($query, '127.0.0.1', $socket = new MockSocket());
        $encodedResponse = $socket->getLastTransmission();
        $response = Decoder::decodeMessage($encodedResponse);

        $this->assertEquals(1, $response->getHeader()->getAnswerCount());
        $this->assertEquals(1, $response->getHeader()->getAdditionalRecordsCount());
        $this->assertEquals('192.168.3.89', $response->getAdditionals()[0]->getRdata());
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testMxAdditionalRecords()
    {
        $queryHeader = (new Header())
            ->setQuery(true)
            ->setOpcode(Header::OPCODE_STANDARD_QUERY)
            ->setId(1234);

        $mxQuestion = (new ResourceRecord())
            ->setQuestion(true)
            ->setType(RecordTypeEnum::TYPE_MX)
            ->setName('example.com.');

        $message = (new Message())
            ->setHeader($queryHeader)
            ->addQuestion($mxQuestion);

        $query = Encoder::encodeMessage($message);
        $this->server->onMessage($query, '127.0.0.1', $socket = new MockSocket());
        $encodedResponse = $socket->getLastTransmission();
        $response = Decoder::decodeMessage($encodedResponse);

        $this->assertEquals(2, $response->getHeader()->getAnswerCount());
        $this->assertEquals(2, $response->getHeader()->getAdditionalRecordsCount());
    }

    /**
     * @throws \yswery\DNS\UnsupportedTypeException
     */
    public function testNsAdditionalRecords()
    {
        $queryHeader = (new Header())
            ->setQuery(true)
            ->setOpcode(Header::OPCODE_STANDARD_QUERY)
            ->setId(1234);

        $nsQuestion = (new ResourceRecord())
            ->setQuestion(true)
            ->setType(RecordTypeEnum::TYPE_NS)
            ->setName('example.com.');

        $message = (new Message())
            ->setHeader($queryHeader)
            ->addQuestion($nsQuestion);

        $query = Encoder::encodeMessage($message);
        $this->server->onMessage($query, '127.0.0.1', $socket = new MockSocket());
        $encodedResponse = $socket->getLastTransmission();
        $response = Decoder::decodeMessage($encodedResponse);

        $this->assertEquals(2, $response->getHeader()->getAnswerCount());
        $this->assertEquals(2, $response->getHeader()->getAdditionalRecordsCount());
    }
}
