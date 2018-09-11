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

    /**
     * Start the server
     */
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
        $message = Decoder::decodeMessage($buffer);
        $responseMessage = clone($message);
        $responseMessage->getHeader()
            ->setResponse(true)
            ->setRecursionAvailable($this->resolver->allowsRecursion())
            ->setAuthoritative($this->resolver->isAuthority($responseMessage->getQuestions()[0]->getName()));

        try {
            $responseMessage->setAnswers($this->resolver->getAnswer($responseMessage->getQuestions()));
            $encodedResponse = Encoder::encodeMessage($responseMessage);
        } catch (UnsupportedTypeException $e) {
            $responseMessage
                ->setAnswers([])
                ->getHeader()->setRcode(Header::RCODE_NOT_IMPLEMENTED);
            $encodedResponse = Encoder::encodeMessage($responseMessage);
        }

        $this->logMessage($responseMessage);

        return $encodedResponse;
    }

    /**
     * @param Message $message
     */
    private function logMessage(Message $message): void
    {
        foreach ($message->getQuestions() as $question) {
            $this->logger->log(LogLevel::INFO, 'Query: ' . $question);
        }

        foreach ($message->getAnswers() as $answer) {
            $this->logger->log(LogLevel::INFO, 'Answer: ' . $answer);
        }
    }
}
