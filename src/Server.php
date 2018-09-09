<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

use Psr\Log\LogLevel;
use React\Datagram\Socket;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Server implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResolverInterface $resolver
     */
    private $resolver;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $defaultTtl;

    /**
     * @var int
     */
    private $packetMaxLength;

    /**
     * Server constructor.
     *
     * @param ResolverInterface $ds_storage
     * @param string $bind_ip
     * @param int $bind_port
     * @param int $default_ttl
     * @param int $max_packet_len
     * @throws \Exception
     */
    public function __construct(ResolverInterface $ds_storage, $bind_ip = '0.0.0.0', $bind_port = 53, $default_ttl = 300, $max_packet_len = 512)
    {
        $this->resolver = $ds_storage;
        $this->port = $bind_port;
        $this->ip = $bind_ip;
        $this->defaultTtl = $default_ttl;
        $this->packetMaxLength = $max_packet_len;
        $this->logger = new NullLogger();

        set_time_limit(0);

        if (!extension_loaded('sockets') || !function_exists('socket_create')) {
            throw new \Exception('Socket extension or socket_create() function not found.');
        }
    }

    public function start()
    {
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);

        $factory->createServer($this->ip.':'.$this->port)->then(function (Socket $server) {
            $this->logger->log(LogLevel::INFO, 'Server started.');
            $server->on('message', function ($message, $address, $server) {
                $response = $this->handleQueryFromStream($message);
                $server->send($response, $address);
            });
        });

        $loop->run();
    }

    /**
     * @param string $buffer
     * @return string
     * @throws UnsupportedTypeException
     */
    public function handleQueryFromStream(string $buffer): string
    {
        $offset = 0;
        $header = Decoder::decodeHeader($buffer, $offset);

        $questions = Decoder::decodeResourceRecords($buffer, $offset, $header->getQuestionCount(), true);
        $authority = Decoder::decodeResourceRecords($buffer, $offset, $header->getAnswerCount());
        $additional = Decoder::decodeResourceRecords($buffer, $offset, $header->getAdditionalRecordsCount());
        $answers = $this->resolver->getAnswer($questions);

        foreach ($questions as $question) {
            $this->logger->log(LogLevel::INFO, 'Query: ' . $question);
        }

        foreach ($answers as $answer) {
            $this->logger->log(LogLevel::INFO, 'Response: ' . $answer);
        }

        $header->setResponse(true);
        $header->setRecursionAvailable($this->resolver->allowsRecursion());
        $header->setAuthoritative($this->resolver->isAuthority(($questions[0])->getName()));

        $header->setAnswerCount(count($answers));

        $response = Encoder::encodeHeader($header);
        $response .= Encoder::encodeResourceRecords($questions);
        $response .= Encoder::encodeResourceRecords($answers);
        $response .= Encoder::encodeResourceRecords($authority);
        $response .= Encoder::encodeResourceRecords($additional);

        return $response;
    }

    /**
     * @param Header $header
     * @return string
     */
    private function errorResponse(Header $header): string
    {
        $header = (new Header)
            ->setId($header->getId())
            ->setRcode(Header::RCODE_SERVER_FAILURE);

        return Encoder::encodeLabel($header);
    }
}
