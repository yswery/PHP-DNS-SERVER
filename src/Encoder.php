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

    /**
     * @param int $type
     * @param string|array $val
     * @return string
     * @throws UnsupportedTypeException
     */
    public static function encodeType($type, $val): string
    {
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
                $enc = self::encodeSOA($val);
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
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::get_name($type))
                );
        }

        return $enc;
    }

    /**
     * @param array $soa
     * @return string
     */
    public static function encodeSOA(array $soa)
    {
        return
            self::encodeLabel($soa['mname']) .
            self::encodeLabel($soa['rname']) .
            pack(
                'NNNNN',
                $soa['serial'],
                $soa['refresh'],
                $soa['retry'],
                $soa['expire'],
                $soa['minimum']
            );
    }

    /**
     * @param ResourceRecord[] $resourceRecords
     * @return string
     * @throws UnsupportedTypeException
     */
    public static function encodeResourceRecords(array $resourceRecords): string
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
