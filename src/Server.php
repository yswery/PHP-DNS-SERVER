<?php

namespace yswery\DNS;

use React\Datagram\Socket;

class Server
{
    /**
     * @var ResolverInterface $ds_storage
     */
    private $ds_storage;

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
        $this->DS_PORT = $bind_port;
        $this->DS_IP = $bind_ip;
        $this->DS_TTL = $default_ttl;
        $this->DS_MAX_LENGTH = $max_packet_len;
        $this->ds_storage = $ds_storage;

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

        $factory->createServer($this->DS_IP.':'.$this->DS_PORT)->then(function (Socket $server) {
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
        $flags = $this->ds_decode_flags($data['flags']);
        $offset = 12;

        $question = $this->ds_decode_question_rr($buffer, $offset, $data['qdcount']);
        $authority = $this->ds_decode_rr($buffer, $offset, $data['nscount']);
        $additional = $this->ds_decode_rr($buffer, $offset, $data['arcount']);
        $answer = $this->ds_storage->getAnswer($question);
        $flags['qr'] = 1;
        $flags['ra'] = $this->ds_storage->allowsRecursion() ? 1 : 0;
        $flags['aa'] = $this->ds_storage->isAuthority($question[0]['qname']) ? 1 : 0;

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

    private function ds_decode_flags($flags)
    {
        $res = array();

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

    private function ds_decode_question_rr($pkt, &$offset, $count)
    {
        $res = array();

        for ($i = 0; $i < $count; ++$i) {
            if ($offset > strlen($pkt)) {
                return false;
            }
            $qname = $this->ds_decode_label($pkt, $offset);
            $tmp = unpack('nqtype/nqclass', substr($pkt, $offset, 4));
            $offset += 4;
            $tmp['qname'] = $qname;
            $res[] = $tmp;
        }
        return $res;
    }

    private function ds_decode_label($pkt, &$offset)
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
            $qname .= substr($pkt, $offset + 1, $len) . '.';
            $offset += $len + 1;
        }

        if (!is_null($end_offset)) {
            $offset = $end_offset;
        }

        return $qname;
    }

    private function ds_decode_rr($pkt, &$offset, $count)
    {
        $res = array();

        for ($i = 0; $i < $count; ++$i) {
            // read qname
            $qname = $this->ds_decode_label($pkt, $offset);
            // read qtype & qclass
            $tmp = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
            $tmp['name'] = $qname;
            $offset += 10;
            $tmp['data'] = $this->ds_decode_type($tmp['type'], substr($pkt, $offset, $tmp['dlength']));
            $offset += $tmp['dlength'];
            $res[] = $tmp;
        }

        return $res;
    }

    private function ds_decode_type($type, $val)
    {
        $data = array();
        $offset = 0;

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $data['value'] = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $data['value'] = $this->ds_decode_label($val, $offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = array();
                $data['value']['mname'] = $this->ds_decode_label($val, $offset);
                $data['value']['rname'] = $this->ds_decode_label($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));

                foreach ($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }

                break;
            case RecordTypeEnum::TYPE_MX:
                $data['value'] = [
                    'priority' => unpack('npriority', $val)['priority'],
                    'host' => $this->ds_decode_label(substr($val, 2), $offset),
                ];
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
                $data['value'] = array('type' => RecordTypeEnum::TYPE_OPT, 'ext_code' => $this->DS_TTL >> 24 & 0xff, 'udp_payload_size' => 4096, 'version' => $this->DS_TTL >> 16 & 0xff, 'flags' => $this->ds_decode_flags($this->DS_TTL & 0xffff));
                break;
            default:
                $data = false;
        }

        return $data;
    }

    /**
     * @param $flags
     * @return int
     * @deprecated
     */
    private function ds_encode_flags($flags)
    {
        return Encoder::encodeFlags($flags);
    }

    /**
     * @param $str
     * @param null $offset
     * @return string
     * @deprecated
     */
    private function ds_encode_label($str, $offset = null)
    {
        return Encoder::encodeLabel($str);
    }

    /**
     * @param $list
     * @param $offset
     * @return string
     * @deprecated
     */
    private function ds_encode_question_rr($list, $offset)
    {
        return Encoder::encodeQuestionResourceRecord($list);
    }

    /**
     * @param $list
     * @param $offset
     * @return string
     * @deprecated
     */
    private function ds_encode_rr($list, $offset)
    {
        return Encoder::encodeResourceRecord($list);
    }

    /**
     * @param $type
     * @param null $val
     * @param null $offset
     * @return string
     * @deprecated
     */
    private function ds_encode_type($type, $val = null, $offset = null)
    {
        return Encoder::encodeType($type, $val);
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
