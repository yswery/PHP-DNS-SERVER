<?php

namespace yswery\DNS;


class Decoder
{
    public static function decodeFlags($flags)
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

    public static function decodeQuestionResourceRecord($pkt, &$offset, $count)
    {
        $res = array();

        for ($i = 0; $i < $count; ++$i) {
            if ($offset > strlen($pkt)) {
                return false;
            }
            $qname = self::decodeLabel($pkt, $offset);
            $tmp = unpack('nqtype/nqclass', substr($pkt, $offset, 4));
            $offset += 4;
            $tmp['qname'] = $qname;
            $res[] = $tmp;
        }
        
        return $res;
    }
    
    public static function decodeLabel($pkt, &$offset)
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

    public static function decodeResourceRecord($pkt, &$offset, $count)
    {
        $res = array();

        for ($i = 0; $i < $count; ++$i) {
            // read qname
            $qname = self::decodeLabel($pkt, $offset);
            // read qtype & qclass
            $tmp = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
            $tmp['name'] = $qname;
            $offset += 10;
            $tmp['data'] = self::decodeType($tmp['type'], substr($pkt, $offset, $tmp['dlength']));
            $offset += $tmp['dlength'];
            $res[] = $tmp;
        }

        return $res;
    }

    public static function decodeType($type, $val)
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
                $data['value'] = self::decodeLabel($val, $offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data['value'] = array();
                $data['value']['mname'] = self::decodeLabel($val, $offset);
                $data['value']['rname'] = self::decodeLabel($val, $offset);
                $next_values = unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset));

                foreach ($next_values as $var => $val) {
                    $data['value'][$var] = $val;
                }

                break;
            case RecordTypeEnum::TYPE_MX:
                $data['value'] = [
                    'priority' => unpack('npriority', $val)['priority'],
                    'host' => self::decodeLabel(substr($val, 2), $offset),
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
            default:
                $data = false;
        }

        return $data;
    }
}