<?php

namespace yswery\DNS;

use React\Datagram\Socket;

class Server
{
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

        $question = Decoder::decodeResourceRecords($buffer, $offset, $header->getQuestionCount(), true);
        $authority = Decoder::decodeResourceRecords($buffer, $offset, $header->getAnswerCount());
        $additional = Decoder::decodeResourceRecords($buffer, $offset, $header->getAdditionalRecordsCount());
        $answer = $this->resolver->getAnswer($question);

        $header->setResponse(true);
        $header->setRecursionAvailable($this->resolver->allowsRecursion());
        $header->setAuthoritative($this->resolver->isAuthority(($question[0])->getName()));

        $header->setAnswerCount(count($answer));

        $response = Encoder::encodeHeader($header);
        $response .= Encoder::encodeResourceRecords($question);
        $response .= Encoder::encodeResourceRecords($answer);
        $response .= Encoder::encodeResourceRecords($authority);
        $response .= Encoder::encodeResourceRecords($additional);

        return $response;
    }
}
