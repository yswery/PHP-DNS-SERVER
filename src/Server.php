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

use React\Datagram\Socket;
use React\Datagram\SocketInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use yswery\DNS\Event\ServerExceptionEvent;
use yswery\DNS\Event\MessageEvent;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerStartEvent;
use yswery\DNS\Resolver\ResolverInterface;
use yswery\DNS\Event\Events;

class Server
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

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
     * @var LoopInterface
     */
    private $loop;

    /**
     * Server constructor.
     *
     * @param ResolverInterface        $resolver
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $ip
     * @param int                      $port
     *
     * @throws \Exception
     */
    public function __construct(ResolverInterface $resolver, EventDispatcherInterface $dispatcher, string $ip = '0.0.0.0', int $port = 53)
    {
        if (!function_exists('socket_create') || !extension_loaded('sockets')) {
            throw new \Exception('Socket extension or socket_create() function not found.');
        }

        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->port = $port;
        $this->ip = $ip;

        $this->loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($this->loop);

        $factory->createServer($this->ip.':'.$this->port)->then(function (Socket $server) {
            $this->dispatcher->dispatch(Events::SERVER_START, new ServerStartEvent($server));
            $server->on('message', [$this, 'onMessage']);
        });
    }

    /**
     * Start the server.
     */
    public function start(): void
    {
        set_time_limit(0);
        $this->loop->run();
    }

    /**
     * @param string          $message
     * @param string          $address
     * @param SocketInterface $socket
     */
    public function onMessage(string $message, string $address, SocketInterface $socket)
    {
        try {
            $this->dispatcher->dispatch(Events::MESSAGE, new MessageEvent($socket, $address, $message));
            $socket->send($this->handleQueryFromStream($message), $address);
        } catch (\Exception $exception) {
            $this->dispatcher->dispatch(Events::SERVER_EXCEPTION, new ServerExceptionEvent($exception));
        }
    }

    /**
     * Decode a message and return an encoded response.
     *
     * @param string $buffer
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public function handleQueryFromStream(string $buffer): string
    {
        $message = Decoder::decodeMessage($buffer);

        $this->dispatcher->dispatch(Events::QUERY_RECEIVE, new QueryReceiveEvent($message));

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

        $this->dispatcher->dispatch(Events::QUERY_RESPONSE, new QueryResponseEvent($responseMessage));

        return $encodedResponse;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @return ResolverInterface
     */
    public function getResolver(): ResolverInterface
    {
        return $this->resolver;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
}
