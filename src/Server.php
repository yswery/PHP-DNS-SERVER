<?php

namespace yswery\DNS;

use yswery\DNS\ResolverInterface;

class Server
{

    /**
     * @var ResolverInterface
     */
    private $ds_storage;

    public function __construct($ds_storage, $bind_ip = '0.0.0.0', $bind_port = 53, $default_ttl = 300, $max_packet_len = 512)
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
        $ds_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!$ds_socket) {
            $error = sprintf('Cannot create socket (socket error: %s).', socket_strerror(socket_last_error($ds_socket)));
            $this->ds_error(E_USER_ERROR, $error, __FILE__, __LINE__);
        }

        if (!socket_bind($ds_socket, $this->DS_IP, $this->DS_PORT)) {
            $error = sprintf('Cannot bind socket to %s:%d (socket error: %s).', $this->DS_IP, $this->DS_PORT, socket_strerror(socket_last_error($ds_socket)));
            $this->ds_error(E_USER_ERROR, $error, __FILE__, __LINE__);
        }

        while (true) {
            $buffer = $ip = $port = null;

            if (!socket_recvfrom($ds_socket, $buffer, $this->DS_MAX_LENGTH, null, $ip, $port)) {
                $error = sprintf('Cannot read from socket ip: %s, port: %d (socket error: %s).', $ip, $port, socket_strerror(socket_last_error($ds_socket)));
                $this->ds_error(E_USER_ERROR, $error, __FILE__, __LINE__);
            } else {
                $response = $this->ds_handle_query($buffer, $ip, $port);

                if (!socket_sendto($ds_socket, $response, strlen($response), 0, $ip, $port)) {
                    $error = sprintf('Cannot send response to socket ip: %s, port: %d (socket error: %s).', $ip, $port, socket_strerror(socket_last_error($ds_socket)));
                }
            }
        }
    }

    private function ds_handle_query($buffer, $ip, $port)
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

        $response = pack('nnnnnn', $data['packet_id'], $this->ds_encode_flags($flags), $qdcount, $ancount, $nscount, $arcount);
        $response .= ($p = $this->ds_encode_question_rr($question, strlen($response)));
        $response .= ($p = $this->ds_encode_rr($answer, strlen($response)));
        $response .= $this->ds_encode_rr($authority, strlen($response));
        $response .= $this->ds_encode_rr($additional, strlen($response));

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

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $data['value'] = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $foo_offset = 0;
                $data['value'] = $this->ds_decode_label($val, $foo_offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = array();
                $offset = 0;
                $data['value']['mname'] = $this->ds_decode_label($val, $offset);
                $data['value']['rname'] = $this->ds_decode_label($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));

                foreach ($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }

                break;
            case RecordTypeEnum::TYPE_MX:
                $tmp = unpack('n', $val);
                $data['value'] = array('priority' => $tmp[0], 'host' => substr($val, 2),);
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

    private function ds_encode_flags($flags)
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

    private function ds_encode_label($str, $offset = null)
    {
        if ($str === '.') {
            return "\0";
        }

        $res = '';
        $in_offset = 0;

        while (false !== $pos = strpos($str, '.', $in_offset)) {
            $res .= chr($pos - $in_offset) . substr($str, $in_offset, $pos - $in_offset);
            $offset += ($pos - $in_offset) + 1;
            $in_offset = $pos + 1;
        }

        return $res . "\0";
    }

    private function ds_encode_question_rr($list, $offset)
    {
        $res = '';

        foreach ($list as $rr) {
            $lbl = $this->ds_encode_label($rr['qname'], $offset);
            $offset += strlen($lbl) + 4;
            $res .= $lbl;
            $res .= pack('nn', $rr['qtype'], $rr['qclass']);
        }

        return $res;
    }

    private function ds_encode_rr($list, $offset)
    {
        $res = '';

        foreach ($list as $rr) {
            $lbl = $this->ds_encode_label($rr['name'], $offset);
            $res .= $lbl;
            $offset += strlen($lbl);

            if (!is_array($rr['data'])) {
                return false;
            }

            $offset += 10;
            $data = $this->ds_encode_type($rr['data']['type'], $rr['data']['value'], $offset);

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
                $res .= pack('nnNn', $data['type'], $data['class'], $data['ttl'], strlen($data['data'])) . $data['data'];
            } else {
                $offset += strlen($data);
                $res .= pack('nnNn', $rr['data']['type'], $rr['class'], $rr['ttl'], strlen($data)) . $data;
            }
        }

        return $res;
    }

    private function ds_encode_type($type, $val = null, $offset = null)
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
                $val = rtrim($val, '.') . '.';
                $enc = $this->ds_encode_label($val, $offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $val['mname'] = rtrim($val['mname'], '.') . '.';
                $val['rname'] = rtrim($val['rname'], '.') . '.';
                $enc .= $this->ds_encode_label($val['mname'], $offset);
                $enc .= $this->ds_encode_label($val['rname'], $offset + strlen($enc));
                $enc .= pack('NNNNN', $val['serial'], $val['refresh'], $val['retry'], $val['expire'], $val['minimum-ttl']);
                break;
            case RecordTypeEnum::TYPE_MX:
                if (!is_array($val)) {
                    $val = array(
                        'priority' => 10,
                        'target' => $val,
                    );
                }

                $enc = pack('n', (int) $val['priority']);
                $enc .= $this->ds_encode_label(rtrim($val['target'], '.') . '.', $offset + 2);
                break;
            case RecordTypeEnum::TYPE_TXT:
                if (strlen($val) > 255) {
                    $val = substr($val, 0, 255);
                }

                $enc = chr(strlen($val)) . $val;
                break;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                $enc = '';
                break;
            case RecordTypeEnum::TYPE_OPT:
                $enc = array(
                    'class' =>  $val['udp_payload_size'],
                    'ttl' =>    (($val['ext_code'] & 0xff) << 24) |
                                (($val['version'] & 0xff) << 16) |
                                ($this->ds_encode_flags($val['flags']) & 0xffff),
                    'data' =>   '', // TODO: encode data
                );
                break;
            default:
                $enc = $val;
        }

        return $enc;
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
