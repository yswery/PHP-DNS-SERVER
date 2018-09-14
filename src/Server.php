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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use React\Datagram\Socket;
use yswery\DNS\Event\EventSubscriberTrait;
use yswery\DNS\Event\MessageEvent;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerStartEvent;
use yswery\DNS\Resolver\ResolverInterface;

class Server implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use EventSubscriberTrait;

    /**
     * @var ResolverInterface
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
     * Server constructor.
     *
     * @param ResolverInterface $resolver
     * @param string            $ip
     * @param int               $port
     *
     * @throws \Exception
     */
    public function __construct(ResolverInterface $resolver, string $ip = '0.0.0.0', int $port = 53)
    {
        $this->resolver = $resolver;
        $this->port = $port;
        $this->ip = $ip;
        $this->logger = new NullLogger();

        set_time_limit(0);

        if (!function_exists('socket_create') || !extension_loaded('sockets')) {
            throw new \Exception('Socket extension or socket_create() function not found.');
        }
    }

    /**
     * Start the server.
     */
    public function start(): void
    {
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);

        $factory->createServer($this->ip.':'.$this->port)->then(function (Socket $server) {
            $this->logger->log(LogLevel::INFO, 'Server started.');
            $this->event(new ServerStartEvent($server));

            $server->on('message', function (string $message, string $address, Socket $server) {
                $this->event(new MessageEvent($server, $address, $message));

                $response = $this->handleQueryFromStream($message);
                $server->send($response, $address);
            });
        });

        $loop->run();
    }

    /**
     * @param string $buffer
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public function handleQueryFromStream(string $buffer): string
    {
        $message = Decoder::decodeMessage($buffer);

        $this->event(new QueryReceiveEvent($message));

        $responseMessage = clone $message;
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

        $this->event(new QueryResponseEvent($responseMessage));
        $this->logMessage($responseMessage);

        return $encodedResponse;
    }

    /**
     * @param Message $message
     */
    private function logMessage(Message $message): void
    {
        foreach ($message->getQuestions() as $question) {
            $this->logger->log(LogLevel::INFO, 'Query: '.$question);
        }

        foreach ($message->getAnswers() as $answer) {
            $this->logger->log(LogLevel::INFO, 'Answer: '.$answer);
        }
    }
}
