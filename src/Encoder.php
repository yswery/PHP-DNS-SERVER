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
     * @param ResourceRecord[] $resourceRecords
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public static function encodeResourceRecords(array $resourceRecords): string
    {
        $records = array_map('self::encodeResourceRecord', $resourceRecords);

        return implode('', $records);
    }

    /**
     * @param ResourceRecord $rr
     *
     * @return string
     *
     * @throws UnsupportedTypeException
     */
    public static function encodeResourceRecord(ResourceRecord $rr): string
    {
        $encoded = self::encodeDomainName($rr->getName());
        if ($rr->isQuestion()) {
            return $encoded.pack('nn', $rr->getType(), $rr->getClass());
        }

        $data = RdataEncoder::encodeRdata($rr->getType(), $rr->getRdata());
        $encoded .= pack('nnNn', $rr->getType(), $rr->getClass(), $rr->getTtl(), strlen($data));

        return $encoded.$data;
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
}
