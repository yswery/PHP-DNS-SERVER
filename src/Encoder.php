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
            return chr(0);
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
     * @param string|array $rdata
     *
     * @return string
     *
     * @throws UnsupportedTypeException|\InvalidArgumentException
     */
    public static function encodeRdata(int $type, $rdata): string
    {
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                if (!filter_var($rdata, FILTER_VALIDATE_IP)) {
                    throw new \InvalidArgumentException(sprintf('The IP address "%s" is invalid.', $rdata));
                }

                return inet_pton($rdata);
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                return self::encodeDomainName($rdata);
            case RecordTypeEnum::TYPE_SOA:
                return self::encodeSOA($rdata);
            case RecordTypeEnum::TYPE_MX:
                return pack('n', (int) $rdata['preference']).self::encodeDomainName($rdata['exchange']);
            case RecordTypeEnum::TYPE_TXT:
                $rdata = substr($rdata, 0, 255);

                return chr(strlen($rdata)).$rdata;
            case RecordTypeEnum::TYPE_SRV:
                return pack('nnn', (int) $rdata['priority'], (int) $rdata['weight'], (int) $rdata['port']).
                    self::encodeDomainName($rdata['target']);
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                return '';
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }
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

            $data = self::encodeRdata($rr->getType(), $rr->getRdata());
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
    private static function encodeFlags(Header $header): int
    {
        return 0x0 |
            ($header->isResponse() & 0x1) << 15 |
            ($header->getOpcode() & 0xf) << 11 |
            ($header->isAuthoritative() & 0x1) << 10 |
            ($header->isTruncated() & 0x1) << 9 |
            ($header->isRecursionDesired() & 0x1) << 8 |
            ($header->isRecursionAvailable() & 0x1) << 7 |
            ($header->getZ() & 0x7) << 4 |
            ($header->getRcode() & 0xf);
    }

    /**
     * @param array $soa
     *
     * @return string
     */
    private static function encodeSOA(array $soa): string
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
}
