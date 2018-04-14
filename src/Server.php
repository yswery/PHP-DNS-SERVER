<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS;

use yswery\DNS\Event\EventSubscriberInterface;
use yswery\DNS\Resolver\ResolverInterface;

/**
 * Class Server
 */
class Server
{
    private $ip = '0.0.0.0';
    private $port = 53;
    private $ttl = 300;
    private $maxPacketLength = 512;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    protected $eventSubscribes = [];

    /**
     * Server constructor.
     *
     * @param ResolverInterface $resolver
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(ResolverInterface $resolver, array $config = [])
    {
        // @todo validate configuration validate ip, port, max packet and ttl as positive integers
        $this->ip = isset($config['bind']) ? $config['bind'] : $this->ip;
        $this->port = isset($config['port']) ? $config['port'] : $this->port;
        $this->ttl = isset($config['ttl']) ? $config['ttl'] : $this->ttl;
        $this->maxPacketLength = isset($config['max_packet_length']) ? $config['max_packet_length'] : $this->maxPacketLength;

        $this->resolver = $resolver;

        set_time_limit(0);

        if (!extension_loaded('sockets') || !function_exists('socket_create')) {
            throw new \Exception('Socket extension or function not found.');
        }
    }

    /**
     * Register event subscriber to class.
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function registerEventSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->eventSubscribes[] = $subscriber;
    }

    /**
     * Starts DNS server.
     *
     * @throws \Exception
     */
    public function run()
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!$socket) {
            $error = sprintf(
                'Cannot create socket (socket error: %s).',
                socket_strerror(socket_last_error($socket))
            );

            throw new \Exception($error);
        }

        if (!socket_bind($socket, $this->ip, $this->port)) {
            $error = sprintf(
                'Cannot bind socket to %s:%d (socket error: %s).',
                $this->ip,
                $this->port,
                socket_strerror(socket_last_error($socket))
            );

            throw new \Exception($error);
        }

        while (true) {
            $buffer = $ip = $port = null;

            if (!socket_recvfrom($socket, $buffer, $this->maxPacketLength, null, $ip, $port)) {
                $error = sprintf(
                    'Cannot read from socket ip: %s, port: %d (socket error: %s).',
                    $ip,
                    $port,
                    socket_strerror(socket_last_error($socket))
                );
                throw new \Exception($error);
            }

            $response = $this->handleQuery($buffer);

            if (!socket_sendto($socket, $response, strlen($response), 0, $ip, $port)) {
                $error = sprintf(
                    'Cannot send reponse to socket ip: %s, port: %d (socket error: %s).',
                    $ip,
                    $port,
                    socket_strerror(socket_last_error($socket))
                );
                throw new \Exception($error);
            }
        }
    }

    /**
     * Handles DNS queries.
     *
     * @param $buffer
     * @return string
     */
    private function handleQuery($buffer)
    {
        $data = unpack('npacket_id/nflags/nqdcount/nancount/nnscount/narcount', $buffer);
        $flags = $this->decodeFlags($data['flags']);
        $offset = 12;

        $question = $this->decodeQuestionRR($buffer, $offset, $data['qdcount']);
        $authority = $this->decodeRR($buffer, $offset, $data['nscount']);
        $additional = $this->decodeRR($buffer, $offset, $data['arcount']);
        $answer = $this->resolver->getAnswer($question);
        $flags['qr'] = 1;
        $flags['ra'] = $this->resolver->allowsRecursion() ? 1 : 0;
        $flags['aa'] = $this->resolver->isAuthority($question[0]['qname']) ? 1 : 0;

        $qdcount = count($question);
        $ancount = count($answer);
        $nscount = count($authority);
        $arcount = count($additional);

        $response = pack(
            'nnnnnn',
            $data['packet_id'],
            $this->encodeFlags($flags),
            $qdcount,
            $ancount,
            $nscount,
            $arcount
        );
        $response .= ($p = $this->encodeQuestionRR($question, strlen($response)));
        $response .= ($p = $this->encodeRR($answer, strlen($response)));
        $response .= $this->encodeRR($authority, strlen($response));
        $response .= $this->encodeRR($additional, strlen($response));

        $this->notifyEventSubscriber(
            'onEvent',
            [
                'query' => $question,
                'answer' => $answer,
            ]
        );

        return $response;
    }

    private function decodeFlags($flags)
    {
        $res = [];

        $res['qr'] = $flags >> 15 & 0x1;
        $res['opcode'] = $flags >> 11 & 0xf;
        $res['aa'] = $flags >> 10 & 0x1;
        $res['tc'] = $flags >> 9 & 0x1;
        $res['rd'] = $flags >> 8 & 0x1;
        $res['ra'] = $flags >> 7 & 0x1;
        $res['z'] = $flags >> 4 & 0x7;
        $res['rcode'] = $flags & 0xf;

        return $res;
    }

    private function decodeQuestionRR($pkt, &$offset, $count)
    {
        $res = [];

        for ($i = 0; $i < $count; ++$i) {
            if ($offset > strlen($pkt)) {
                return false;
            }
            $qname = $this->decodeLabel($pkt, $offset);
            $tmp = unpack('nqtype/nqclass', substr($pkt, $offset, 4));
            $offset += 4;
            $tmp['qname'] = $qname;
            $res[] = $tmp;
        }

        return $res;
    }

    private function decodeLabel($pkt, &$offset)
    {
        $end_offset = null;
        $qname = '';

        while (1) {
            $len = ord($pkt[$offset]);
            $type = $len >> 6 & 0x2;

            if ($type) {
                switch ($type) {
                    case 0x2:
                        $new_offset = unpack('noffset', substr($pkt, $offset, 2));
                        $end_offset = $offset + 2;
                        $offset = $new_offset['offset'] & 0x3fff;
                        break;
                    case 0x1:
                        break;
                }
                continue;
            }

            if ($len > (strlen($pkt) - $offset)) {
                return null;
            }

            if ($len == 0) {
                if ($qname == '') {
                    $qname = '.';
                }
                ++$offset;
                break;
            }
            $qname .= substr($pkt, $offset + 1, $len).'.';
            $offset += $len + 1;
        }

        if (!is_null($end_offset)) {
            $offset = $end_offset;
        }

        return $qname;
    }

    private function decodeRR($pkt, &$offset, $count)
    {
        $res = [];

        for ($i = 0; $i < $count; ++$i) {
            // read qname
            $qname = $this->decodeLabel($pkt, $offset);
            // read qtype & qclass
            $tmp = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
            $tmp['name'] = $qname;
            $offset += 10;
            $tmp['data'] = $this->decodeType($tmp['type'], substr($pkt, $offset, $tmp['dlength']));
            $offset += $tmp['dlength'];
            $res[] = $tmp;
        }

        return $res;
    }

    private function decodeType($type, $val)
    {
        $data = [];

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $data['value'] = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $foo_offset = 0;
                $data['value'] = $this->decodeLabel($val, $foo_offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = [];
                $offset = 0;
                $data['value']['mname'] = $this->decodeLabel($val, $offset);
                $data['value']['rname'] = $this->decodeLabel($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));

                foreach ($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }

                break;
            case RecordTypeEnum::TYPE_MX:
                $tmp = unpack('n', $val);
                $data['value'] = ['priority' => $tmp[0], 'host' => substr($val, 2)];
                break;
            case RecordTypeEnum::TYPE_TXT:
                $len = ord($val[0]);

                if ((strlen($val) + 1) < $len) {
                    $data['value'] = null;
                    break;
                }

                $data['value'] = substr($val, 1, $len);
                break;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                $data['value'] = null;
                break;
            case RecordTypeEnum::TYPE_OPT:
                $data['type'] = RecordTypeEnum::TYPE_OPT;
                $data['value'] = [
                    'type' => RecordTypeEnum::TYPE_OPT,
                    'ext_code' => $this->ttl >> 24 & 0xff,
                    'udp_payload_size' => 4096,
                    'version' => $this->ttl >> 16 & 0xff,
                    'flags' => $this->decodeFlags($this->ttl & 0xffff),
                ];
                break;
            default:
                $data = false;
        }

        return $data;
    }

    private function encodeFlags($flags)
    {
        $val = 0;

        $val |= ($flags['qr'] & 0x1) << 15;
        $val |= ($flags['opcode'] & 0xf) << 11;
        $val |= ($flags['aa'] & 0x1) << 10;
        $val |= ($flags['tc'] & 0x1) << 9;
        $val |= ($flags['rd'] & 0x1) << 8;
        $val |= ($flags['ra'] & 0x1) << 7;
        $val |= ($flags['z'] & 0x7) << 4;
        $val |= ($flags['rcode'] & 0xf);

        return $val;
    }

    private function encodeLabel($str, $offset = null)
    {
        if ('.' === $str) {
            return "\0";
        }

        $res = '';
        $in_offset = 0;

        while (false !== $pos = strpos($str, '.', $in_offset)) {
            $res .= chr($pos - $in_offset).substr($str, $in_offset, $pos - $in_offset);
            $offset += ($pos - $in_offset) + 1;
            $in_offset = $pos + 1;
        }

        return $res."\0";
    }

    private function encodeQuestionRR($list, $offset)
    {
        $res = '';

        foreach ($list as $rr) {
            $lbl = $this->encodeLabel($rr['qname'], $offset);
            $offset += strlen($lbl) + 4;
            $res .= $lbl;
            $res .= pack('nn', $rr['qtype'], $rr['qclass']);
        }

        return $res;
    }

    private function encodeRR($list, $offset)
    {
        $res = '';

        foreach ($list as $rr) {
            $lbl = $this->encodeLabel($rr['name'], $offset);
            $res .= $lbl;
            $offset += strlen($lbl);

            if (!is_array($rr['data'])) {
                return false;
            }

            $offset += 10;
            $data = $this->encodeType($rr['data']['type'], $rr['data']['value'], $offset);

            if (is_array($data)) {
                // overloading written data
                if (!isset($data['type'])) {
                    $data['type'] = $rr['data']['type'];
                }
                if (!isset($data['data'])) {
                    $data['data'] = '';
                }
                if (!isset($data['class'])) {
                    $data['class'] = $rr['class'];
                }
                if (!isset($data['ttl'])) {
                    $data['ttl'] = $rr['ttl'];
                }
                $offset += strlen($data['data']);
                $res .= pack('nnNn', $data['type'], $data['class'], $data['ttl'], strlen($data['data'])).$data['data'];
            } else {
                $offset += strlen($data);
                $res .= pack('nnNn', $rr['data']['type'], $rr['class'], $rr['ttl'], strlen($data)).$data;
            }
        }

        return $res;
    }

    private function encodeType($type, $val = null, $offset = null)
    {
        $enc = '';
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $enc = inet_pton($val);
                // Check that the IP address is valid, if not, return an invalid address
                $n = (RecordTypeEnum::TYPE_A === $type) ? 4 : 16;
                if (strlen($enc) !== $n) {
                    $enc = str_repeat("\0", $n);
                }
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $val = rtrim($val, '.').'.';
                $enc = $this->encodeLabel($val, $offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $val['mname'] = rtrim($val['mname'], '.').'.';
                $val['rname'] = rtrim($val['rname'], '.').'.';
                $enc .= $this->encodeLabel($val['mname'], $offset);
                $enc .= $this->encodeLabel($val['rname'], $offset + strlen($enc));
                $enc .= pack(
                    'NNNNN',
                    $val['serial'],
                    $val['refresh'],
                    $val['retry'],
                    $val['expire'],
                    $val['minimum-ttl']
                );
                break;
            case RecordTypeEnum::TYPE_MX:
                if (!is_array($val)) {
                    $val = [
                        'priority' => 10,
                        'target' => $val,
                    ];
                }

                $enc = pack('n', (int)$val['priority']);
                $enc .= $this->encodeLabel(rtrim($val['target'], '.').'.', $offset + 2);
                break;
            case RecordTypeEnum::TYPE_TXT:
                if (strlen($val) > 255) {
                    $val = substr($val, 0, 255);
                }

                $enc = chr(strlen($val)).$val;
                break;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                $enc = '';
                break;
            case RecordTypeEnum::TYPE_OPT:
                $enc = [
                    'class' => $val['udp_payload_size'],
                    'ttl' => (($val['ext_code'] & 0xff) << 24) |
                        (($val['version'] & 0xff) << 16) |
                        ($this->encodeFlags($val['flags']) & 0xffff),
                    'data' => '', // TODO: encode data
                ];
                break;
            default:
                $enc = $val;
        }

        return $enc;
    }

    /**
     * @param string $event
     * @param array  $data
     */
    private function notifyEventSubscriber($event, array $data)
    {
        /** @var EventSubscriberInterface $subscriber */
        foreach ($this->eventSubscribes as $subscriber) {
            call_user_func_array([$subscriber, $event], [$data]);
        }
    }
}
