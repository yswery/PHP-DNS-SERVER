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

    private function ds_handle_query($buffer)
    {
        $data = unpack('npacket_id/nflags/nqdcount/nancount/nnscount/narcount', $buffer);
        $flags = Decoder::decodeFlags($data['flags']);
        $offset = 12;

        $question = Decoder::decodeQuestionResourceRecord($buffer, $offset, $data['qdcount']);
        $authority = Decoder::decodeResourceRecord($buffer, $offset, $data['nscount']);
        $additional = Decoder::decodeResourceRecord($buffer, $offset, $data['arcount']);
        $answer = $this->resolver->getAnswer($question);

        $flags['qr'] = 1;
        $flags['ra'] = $this->resolver->allowsRecursion() ? 1 : 0;
        $flags['aa'] = $this->resolver->isAuthority($question[0]['qname']) ? 1 : 0;

        $qdcount = count($question);
        $ancount = count($answer);
        $nscount = count($authority);
        $arcount = count($additional);

        $response = pack('nnnnnn', $data['packet_id'], Encoder::encodeFlags($flags), $qdcount, $ancount, $nscount, $arcount);
        $response .= Encoder::encodeQuestionResourceRecord($question);
        $response .= Encoder::encodeResourceRecord($answer);
        $response .= Encoder::encodeResourceRecord($authority);
        $response .= Encoder::encodeResourceRecord($additional);

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
