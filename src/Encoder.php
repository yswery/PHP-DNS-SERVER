<?php

namespace yswery\DNS;


class Encoder
{
    public static function encodeFlags($flags)
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
    
    public static function encodeLabel($label)
    {
        if ($label === '.') {
            return "\0";
        }

        $res = '';
        $offset = 0;

        while (false !== $pos = strpos($label, '.', $offset)) {
            $res .= chr($pos - $offset) . substr($label, $offset, $pos - $offset);
            $offset = $pos + 1;
        }
        
        $res .= "\0";

        return $res;
    }

    public static function encodeQuestionResourceRecord(array $list)
    {
        $res = '';

        foreach ($list as $rr) {
            $res .= self::encodeLabel($rr['qname']);
            $res .= pack('nn', $rr['qtype'], $rr['qclass']);
        }

        return $res;
    }

    public static function encodeType($type, $val = null)
    {
        $enc = '';
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $n = (RecordTypeEnum::TYPE_A === $type) ? 4 : 16;
                $enc = filter_var($val, FILTER_VALIDATE_IP) ? inet_pton($val) : str_repeat("\0", $n);
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $val = rtrim($val, '.') . '.';
                $enc = self::encodeLabel($val);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $val['mname'] = rtrim($val['mname'], '.') . '.';
                $val['rname'] = rtrim($val['rname'], '.') . '.';
                $enc .= self::encodeLabel($val['mname']);
                $enc .= self::encodeLabel($val['rname']);
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
                $enc .= self::encodeLabel(rtrim($val['target'], '.') . '.');
                break;
            case RecordTypeEnum::TYPE_TXT:
                $val = substr($val, 0, 255);
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
                        (self::encodeFlags($val['flags']) & 0xffff),
                    'data' =>   '', // TODO: encode data
                );
                break;
            default:
                $enc = $val;
        }

        return $enc;
    }

    public static function encodeResourceRecord(array $resourceRecords)
    {
        $res = '';

        foreach ($resourceRecords as $rr) {
            $lbl = self::encodeLabel($rr['name']);
            $res .= $lbl;

            if (!is_array($rr['data'])) {
                throw new \UnexpectedValueException(sprintf('Resource Record data must by of type "Array", "%s" given.', gettype($rr['data'])));
            }
            $data = self::EncodeType($rr['data']['type'], $rr['data']['value']);

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
                $res .= pack('nnNn', $data['type'], $data['class'], $data['ttl'], strlen($data['data'])) . $data['data'];
            } else {
                $res .= pack('nnNn', $rr['data']['type'], $rr['class'], $rr['ttl'], strlen($data)) . $data;
            }
        }

        return $res;
    }
}