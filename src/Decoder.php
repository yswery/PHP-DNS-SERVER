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

class Decoder
{
    /**
     * @param string $message
     *
     * @return Message
     *
     * @throws UnsupportedTypeException
     */
    public static function decodeMessage(string $message): Message
    {
        $offset = 0;
        $header = self::decodeHeader($message, $offset);

        return (new Message($header))
            ->setQuestions(self::decodeResourceRecords($message, $header->getQuestionCount(), $offset, true))
            ->setAnswers(self::decodeResourceRecords($message, $header->getAnswerCount(), $offset))
            ->setAuthoritatives(self::decodeResourceRecords($message, $header->getNameServerCount(), $offset))
            ->setAdditionals(self::decodeResourceRecords($message, $header->getAdditionalRecordsCount(), $offset));
    }

    /**
     * @param string $string
     * @param int    $offset
     *
     * @return string
     */
    public static function decodeDomainName(string $string, int &$offset = 0): string
    {
        $len = ord($string[$offset]);
        ++$offset;

        if (0 === $len) {
            return '.';
        }

        $domainName = '';
        while (0 !== $len) {
            $domainName .= substr($string, $offset, $len).'.';
            $offset += $len;
            $len = ord($string[$offset]);
            ++$offset;
        }

        return $domainName;
    }

    /**
     * @param string $pkt
     * @param int    $offset
     * @param int    $count      The number of resource records to decode
     * @param bool   $isQuestion Is the resource record from the question section
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    public static function decodeResourceRecords(string $pkt, int $count = 1, int &$offset = 0, bool $isQuestion = false): array
    {
        $resourceRecords = [];

        for ($i = 0; $i < $count; ++$i) {
            ($rr = new ResourceRecord())
                ->setQuestion($isQuestion)
                ->setName(self::decodeDomainName($pkt, $offset));

            if ($rr->isQuestion()) {
                $values = unpack('ntype/nclass', substr($pkt, $offset, 4));
                $rr->setType($values['type'])->setClass($values['class']);
                $offset += 4;
            } else {
                $values = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
                $rr->setType($values['type'])->setClass($values['class'])->setTtl($values['ttl']);
                $offset += 10;
                $rr->setRdata(self::decodeRdata($rr->getType(), substr($pkt, $offset, $values['dlength'])));
                $offset += $values['dlength'];
            }

            $resourceRecords[] = $rr;
        }

        return $resourceRecords;
    }

    /**
     * @param int    $type
     * @param string $rdata
     *
     * @return array|string|null
     *
     * @throws UnsupportedTypeException
     */
    public static function decodeRdata(int $type, string $rdata)
    {
        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                return inet_ntop($rdata);
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                return self::decodeDomainName($rdata);
            case RecordTypeEnum::TYPE_SOA:
                $offset = 0;

                return array_merge(
                    [
                        'mname' => self::decodeDomainName($rdata, $offset),
                        'rname' => self::decodeDomainName($rdata, $offset),
                    ],
                    unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($rdata, $offset))
                );
            case RecordTypeEnum::TYPE_MX:
                return [
                    'preference' => unpack('npreference', $rdata)['preference'],
                    'exchange' => self::decodeDomainName(substr($rdata, 2)),
                ];
            case RecordTypeEnum::TYPE_TXT:
                $len = ord($rdata[0]);
                if ((strlen($rdata) + 1) < $len) {
                    return null;
                }

                return substr($rdata, 1, $len);
            case RecordTypeEnum::TYPE_SRV:
                $offset = 6;
                $values = unpack('npriority/nweight/nport', $rdata);
                $values['target'] = self::decodeDomainName($rdata, $offset);

                return $values;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                return null;
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }
    }

    /**
     * @param string $pkt
     * @param int    $offset
     *
     * @return Header
     */
    public static function decodeHeader(string $pkt, int &$offset = 0): Header
    {
        $data = unpack('nid/nflags/nqdcount/nancount/nnscount/narcount', $pkt);
        $flags = self::decodeFlags($data['flags']);
        $offset += 12;

        return (new Header())
            ->setId($data['id'])
            ->setResponse($flags['qr'])
            ->setOpcode($flags['opcode'])
            ->setAuthoritative($flags['aa'])
            ->setTruncated($flags['tc'])
            ->setRecursionDesired($flags['rd'])
            ->setRecursionAvailable($flags['ra'])
            ->setZ($flags['z'])
            ->setRcode($flags['rcode'])
            ->setQuestionCount($data['qdcount'])
            ->setAnswerCount($data['ancount'])
            ->setNameServerCount($data['nscount'])
            ->setAdditionalRecordsCount($data['arcount']);
    }

    /**
     * @param string $flags
     *
     * @return array
     */
    private static function decodeFlags($flags): array
    {
        return [
            'qr' => $flags >> 15 & 0x1,
            'opcode' => $flags >> 11 & 0xf,
            'aa' => $flags >> 10 & 0x1,
            'tc' => $flags >> 9 & 0x1,
            'rd' => $flags >> 8 & 0x1,
            'ra' => $flags >> 7 & 0x1,
            'z' => $flags >> 4 & 0x7,
            'rcode' => $flags & 0xf,
        ];
    }
}
