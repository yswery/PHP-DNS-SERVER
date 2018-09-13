<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

class Encoder
{
    /**
     * @param Message $message
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public static function encodeMessage(Message $message): string
    {
        return
            self::encodeHeader($message->getHeader()).
            self::encodeResourceRecords($message->getQuestions()).
            self::encodeResourceRecords($message->getAnswers()).
            self::encodeResourceRecords($message->getAuthoritatives()).
            self::encodeResourceRecords($message->getAdditionals());
    }

    /**
     * Encode a domain name as a sequence of labels.
     *
     * @param $domain
     *
     * @return string
     */
    public static function encodeDomainName($domain): string
    {
        if ('.' === $domain) {
            return "\0";
        }

        $domain = rtrim($domain, '.').'.';
        $res = '';

        foreach (explode('.', $domain) as $label) {
            $res .= chr(strlen($label)).$label;
        }

        return $res;
    }

    /**
     * @param int          $type
     * @param string|array $val
     *
     * @return string
     *
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
                $enc = self::encodeDomainName($val);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $enc = self::encodeSOA($val);
                break;
            case RecordTypeEnum::TYPE_MX:
                $enc = pack('n', (int) $val['preference']);
                $enc .= self::encodeDomainName($val['exchange']);
                break;
            case RecordTypeEnum::TYPE_TXT:
                $val = substr($val, 0, 255);
                $enc = chr(strlen($val)).$val;
                break;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                $enc = '';
                break;
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }

        return $enc;
    }

    /**
     * @param array $soa
     *
     * @return string
     */
    public static function encodeSOA(array $soa): string
    {
        return
            self::encodeDomainName($soa['mname']).
            self::encodeDomainName($soa['rname']).
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
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public static function encodeResourceRecords(array $resourceRecords): string
    {
        $res = '';

        foreach ($resourceRecords as $rr) {
            $res .= self::encodeDomainName($rr->getName());
            if ($rr->isQuestion()) {
                $res .= pack('nn', $rr->getType(), $rr->getClass());
                continue;
            }

            $data = self::encodeType($rr->getType(), $rr->getRdata());
            $res .= pack('nnNn', $rr->getType(), $rr->getClass(), $rr->getTtl(), strlen($data));
            $res .= $data;
        }

        return $res;
    }

    /**
     * @param Header $header
     *
     * @return string
     */
    public static function encodeHeader(Header $header): string
    {
        return pack(
            'nnnnnn',
            $header->getId(),
            self::encodeFlags($header),
            $header->getQuestionCount(),
            $header->getAnswerCount(),
            $header->getNameServerCount(),
            $header->getAdditionalRecordsCount()
        );
    }

    /**
     * Encode the bit field of the Header between "ID" and "QDCOUNT".
     *
     * @param Header $header
     *
     * @return int
     */
    public static function encodeFlags(Header $header): int
    {
        $val = 0;

        $val |= ((int) $header->isResponse() & 0x1) << 15;
        $val |= ($header->getOpcode() & 0xf) << 11;
        $val |= ((int) $header->isAuthoritative() & 0x1) << 10;
        $val |= ((int) $header->isTruncated() & 0x1) << 9;
        $val |= ((int) $header->isRecursionDesired() & 0x1) << 8;
        $val |= ((int) $header->isRecursionAvailable() & 0x1) << 7;
        $val |= ($header->getZ() & 0x7) << 4;
        $val |= ($header->getRcode() & 0xf);

        return $val;
    }
}
