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
    private  $packetMaxLength;

    /**
     * Server constructor.
     *
     * @param ResolverInterface $ds_storage
     * @param string $bind_ip
     * @param int $bind_port
     * @param int $default_ttl
     * @param int $max_packet_len
     */
    public function __construct(ResolverInterface $ds_storage, $bind_ip = '0.0.0.0', $bind_port = 53, $default_ttl = 300, $max_packet_len = 512)
    {
        $this->resolver = $ds_storage;
        $this->port = $bind_port;
        $this->ip = $bind_ip;
        $this->defaultTtl = $default_ttl;
        $this->packetMaxLength = $max_packet_len;

        ini_set('display_errors', true);
        ini_set('error_reporting', E_ALL);

        set_error_handler(array($this, 'ds_error'), E_ALL);
        set_time_limit(0);

        if (!extension_loaded('sockets') || !function_exists('socket_create')) {
            $this->ds_error(E_USER_ERROR, 'Socket extension or function not found.', __FILE__, __LINE__);
        }
    }

    public function start()
    {
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);

        $factory->createServer($this->ip.':'.$this->port)->then(function (Socket $server) {
            $server->on('message', function($message, $address, $server) {
                $response = $this->ds_handle_query($message);
                $server->send($response, $address);
            });
        });

        $loop->run();
    }

    /**
     * @param $buffer
     * @return string
     * @throws UnsupportedTypeException
     */
    private function ds_handle_query($buffer)
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

    public function ds_error($code, $error, $file, $line)
    {
        if (!(error_reporting() & $code)) {
            return;
        }

        $codes = array(E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parse Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Strict Notice', E_RECOVERABLE_ERROR => 'Recoverable Error', E_DEPRECATED => 'Deprecated Error', E_USER_DEPRECATED => 'User Deprecated Error');

        $type = isset($codes[$code]) ? $codes[$code] : 'Unknown Error';

        die(sprintf('DNS Server error: [%s] "%s" in file "%s" on line "%d".%s', $type, $error, $file, $line, PHP_EOL));
    }
}
