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
use Symfony\Component\EventDispatcher\Event;
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

    private $allowedomain;
    private $allowedip;
    private $allowediptime;

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
    public function __construct(ResolverInterface $resolver, ?EventDispatcherInterface $dispatcher = null, string $ip = '0.0.0.0', int $port = 53, $allowedomain = 'all')
    {
        if (!function_exists('socket_create') || !extension_loaded('sockets')) {
            throw new \Exception('Socket extension or socket_create() function not found.');
        }

        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->port = $port;
	    $this->ip = $ip;
	    $this->allowedomain = $allowedomain;

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

    /**
     * This methods gets called each time a query is received.
     *
     * @param string          $message
     * @param string          $address
     * @param SocketInterface $socket
     */
    public function onMessage(string $message, string $address, SocketInterface $socket)
    {
        if( !$this->checkAllowedIp( $address ) ){
	        $this->dispatch(Events::SERVER_EXCEPTION, new ServerExceptionEvent( new \Exception("Not allowed!")) );
        }
        else{
            try {
                $this->dispatch(Events::MESSAGE, new MessageEvent($socket, $address, $message));
                $socket->send($this->handleQueryFromStream($message), $address);
            } catch (\Exception $exception) {
       	        $this->dispatch(Events::SERVER_EXCEPTION, new ServerExceptionEvent($exception));
            }
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
        $this->dispatch(Events::QUERY_RECEIVE, new QueryReceiveEvent($message));

        $responseMessage = clone $message;
        $responseMessage->getHeader()
            ->setResponse(true)
            ->setRecursionAvailable($this->resolver->allowsRecursion())
            ->setAuthoritative($this->isAuthoritative($message->getQuestions()));

        try {
            $answers = $this->resolver->getAnswer($responseMessage->getQuestions());
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
    private function needsAdditionalRecords(Message $message): void
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
    private function isAuthoritative(array $query): bool
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
    private function dispatch($eventName, ?Event $event = null): ?Event
    {
        if (null === $this->dispatcher) {
            return null;
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }

    private function checkAllowedIp( string $address ) : bool {
        if( $this->allowedomain != 'all' ){
            $ips = array();
            if( preg_match( '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $address, $ips) ){
                $ip = $ips[1];
                if( empty($this->allowedip) || $this->allowediptime + 300 < time() ){
                    $this->allowedip = \gethostbyname( $this->allowedomain );
                    $this->allowediptime = time();
                }
                return $ip == $this->allowedip;
            }
            return false;
        }
        else{
            return true;
        }
    }
}
