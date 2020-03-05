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
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use yswery\DNS\Config\FileConfig;
use yswery\DNS\Event\Events;
use yswery\DNS\Event\MessageEvent;
use yswery\DNS\Event\QueryReceiveEvent;
use yswery\DNS\Event\QueryResponseEvent;
use yswery\DNS\Event\ServerExceptionEvent;
use yswery\DNS\Event\ServerStartEvent;
use yswery\DNS\Filesystem\FilesystemManager;
use yswery\DNS\Resolver\JsonFileSystemResolver;
use yswery\DNS\Resolver\ResolverInterface;

class Server
{
    /**
     * The version of PhpDnsServer we are running.
     *
     * @var string
     */
    const VERSION = '1.4.0';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var FilesystemManager
     */
    private $filesystemManager;

    /**
     * @var FileConfig
     */
    private $config;

    /**
     * @var bool
     */
    private $useFilesystem;

    /**
     * @var bool
     */
    private $isWindows;

    /**
     * Server constructor.
     *
     * @param ResolverInterface        $resolver
     * @param EventDispatcherInterface $dispatcher
     * @param FileConfig               $config
     * @param string|null              $storageDirectory
     * @param bool                     $useFilesystem
     * @param string                   $ip
     * @param int                      $port
     *
     * @throws \Exception
     */
    public function __construct(?ResolverInterface $resolver = null, ?EventDispatcherInterface $dispatcher = null, ?FileConfig $config = null, string $storageDirectory = null, bool $useFilesystem = false, string $ip = '0.0.0.0', int $port = 53)
    {
        if (!function_exists('socket_create') || !extension_loaded('sockets')) {
            throw new \Exception('Socket extension or socket_create() function not found.');
        }

        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->config = $config;
        $this->port = $port;
        $this->ip = $ip;
        $this->useFilesystem = $useFilesystem;

        // detect os
        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            $this->isWindows = true;
        } else {
            $this->isWindows = false;
        }

        // only register filesystem if we want to use it
        if ($useFilesystem) {
            $this->filesystemManager = new FilesystemManager($storageDirectory);
            $this->resolver = new JsonFileSystemResolver($this->filesystemManager);
        }

        $this->loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($this->loop);
        $factory->createServer($this->ip.':'.$this->port)->then(function (Socket $server) {
            $this->dispatch(Events::SERVER_START, new ServerStartEvent($server));
            $server->on('message', [$this, 'onMessage']);
        })->otherwise(function (\Exception $exception) {
            $this->dispatch(Events::SERVER_START_FAIL, new ServerExceptionEvent($exception));
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

    public function run()
    {
        $this->start();
    }

    /**
     * This methods gets called each time a query is received.
     *
     * @param string          $message
     * @param string          $address
     * @param SocketInterface $socket
     */
    public function onMessage(string $message, string $address, SocketInterface $socket)
    {
        try {
            $this->dispatch(Events::MESSAGE, new MessageEvent($socket, $address, $message));
            $socket->send($this->handleQueryFromStream($message, $address), $address);
        } catch (\Exception $exception) {
            $this->dispatch(Events::SERVER_EXCEPTION, new ServerExceptionEvent($exception));
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
    public function handleQueryFromStream(string $buffer, ?string $client = null): string
    {
        $message = Decoder::decodeMessage($buffer);
        $this->dispatch(Events::QUERY_RECEIVE, new QueryReceiveEvent($message));

        $responseMessage = clone $message;
        $responseMessage->getHeader()
            ->setResponse(true)
            ->setRecursionAvailable($this->resolver->allowsRecursion())
            ->setAuthoritative($this->isAuthoritative($message->getQuestions()));

        try {
            $answers = $this->resolver->getAnswer($responseMessage->getQuestions(), $client);
            $responseMessage->setAnswers($answers);
            $this->needsAdditionalRecords($responseMessage);
            $this->dispatch(Events::QUERY_RESPONSE, new QueryResponseEvent($responseMessage));

            return Encoder::encodeMessage($responseMessage);
        } catch (UnsupportedTypeException $e) {
            $responseMessage
                ->setAnswers([])
                ->getHeader()->setRcode(Header::RCODE_NOT_IMPLEMENTED);
            $this->dispatch(Events::QUERY_RESPONSE, new QueryResponseEvent($responseMessage));

            return Encoder::encodeMessage($responseMessage);
        }
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

    /**
     * Populate the additional records of a message if required.
     *
     * @param Message $message
     */
    protected function needsAdditionalRecords(Message $message): void
    {
        foreach ($message->getAnswers() as $answer) {
            $name = null;
            switch ($answer->getType()) {
                case RecordTypeEnum::TYPE_NS:
                    $name = $answer->getRdata();
                    break;
                case RecordTypeEnum::TYPE_MX:
                    $name = $answer->getRdata()['exchange'];
                    break;
                case RecordTypeEnum::TYPE_SRV:
                    $name = $answer->getRdata()['target'];
                    break;
            }

            if (null === $name) {
                continue;
            }

            $query = [
                (new ResourceRecord())
                    ->setQuestion(true)
                    ->setType(RecordTypeEnum::TYPE_A)
                    ->setName($name),

                (new ResourceRecord())
                    ->setQuestion(true)
                    ->setType(RecordTypeEnum::TYPE_AAAA)
                    ->setName($name),
            ];

            foreach ($this->resolver->getAnswer($query) as $additional) {
                $message->addAdditional($additional);
            }
        }
    }

    /**
     * @param ResourceRecord[] $query
     *
     * @return bool
     */
    protected function isAuthoritative(array $query): bool
    {
        if (empty($query)) {
            return false;
        }

        $authoritative = true;
        foreach ($query as $rr) {
            $authoritative &= $this->resolver->isAuthority($rr->getName());
        }

        return $authoritative;
    }

    /**
     * @param string     $eventName
     * @param Event|null $event
     *
     * @return Event|null
     */
    protected function dispatch($eventName, ?Event $event = null): ?Event
    {
        if (null === $this->dispatcher) {
            return null;
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }
}
