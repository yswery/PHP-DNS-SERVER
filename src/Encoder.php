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
    
    public static function encodeLabel($domain)
    {
        if ('.' === $domain) {
            return "\0";
        }

        $domain = rtrim($domain, '.') . '.';
        $res = '';

        foreach (explode('.', $domain) as $label) {
            $res .= chr(strlen($label)) . $label;
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
                $enc = self::encodeLabel($val);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $enc .= self::encodeLabel($val['mname']);
                $enc .= self::encodeLabel($val['rname']);
                $enc .= pack('NNNNN', $val['serial'], $val['refresh'], $val['retry'], $val['expire'], $val['minimum']);
                break;
            case RecordTypeEnum::TYPE_MX:
                $enc = pack('n', (int) $val['preference']);
                $enc .= self::encodeLabel($val['exchange']);
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

    /**
     * @param ResourceRecord[] $resourceRecords
     * @return string
     */
    public static function encodeResourceRecords(array $resourceRecords)
    {
        $res = '';

        foreach ($resourceRecords as $rr) {
            $res .= self::encodeLabel($rr->getName());
            if ($rr->isQuestion()) {
                $res .= pack('nn', $rr->getType(), $rr->getClass());
                continue;
            }

            $data = self::EncodeType($rr->getType(), $rr->getRdata());
            $res .= pack('nnNn', $rr->getType(), $rr->getClass(), $rr->getTtl(), strlen($data));
            $res .= $data;
        }

        return $res;
    }

    /**
     * @param Header $header
     * @return string
     */
    public static function encodeHeader(Header $header)
    {
        return pack(
            'nnnnnn',
            $header->getId(),
            self::encodeFlags($header->asArray()),
            $header->getQuestionCount(),
            $header->getAnswerCount(),
            $header->getNameServerCount(),
            $header->getAdditionalRecordsCount()
        );
    }
}