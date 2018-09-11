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
     * @param $message
     * @return Message
     * @throws UnsupportedTypeException
     */
    public static function decodeMessage($message): Message
    {
        $offset = 0;
        $header = self::decodeHeader($message, $offset);

        return (new Message($header))
            ->setQuestions(self::decodeResourceRecords($message, $offset, $header->getQuestionCount(), true))
            ->setAnswers(self::decodeResourceRecords($message,$offset, $header->getAnswerCount()))
            ->setAuthoritatives(self::decodeResourceRecords($message, $offset, $header->getNameServerCount()))
            ->setAdditionals(self::decodeResourceRecords($message, $offset, $header->getAdditionalRecordsCount()));
    }

    /**
     * @param string $flags
     * @return array
     */
    public static function decodeFlags($flags): array
    {
        $res = [];

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

    public static function decodeLabel($pkt, &$offset): ?string
    {
        $endOffset = null;
        $qname = '';

        while (1) {
            $len = \ord($pkt[$offset]);
            $type = $len >> 6 & 0x2;

            switch ($type) {
                case 0x2:
                    $new_offset = unpack('noffset', substr($pkt, $offset, 2));
                    $endOffset = $offset + 2;
                    $offset = $new_offset['offset'] & 0x3fff;
                    // no break
                case 0x1:
                    continue;
                    break;
            }

            if ($len > (\strlen($pkt) - $offset)) {
                return null;
            }

            if ($len === 0) {
                if ($qname === '') {
                    $qname = '.';
                }
                ++$offset;
                break;
            }
            $qname .= substr($pkt, $offset + 1, $len) . '.';
            $offset += $len + 1;
        }

        if ($endOffset !== null) {
            $offset = $endOffset;
        }

        return $qname;
    }

    /**
     * @param string $pkt
     * @param int $offset
     * @param int $count
     * @param bool $isQuestion
     * @return ResourceRecord[]
     * @throws UnsupportedTypeException
     */
    public static function decodeResourceRecords($pkt, &$offset, $count, $isQuestion = false): array
    {
        $resourceRecords = [];

        for ($i = 0; $i < $count; ++$i) {
            ($rr = new ResourceRecord)
                ->setQuestion($isQuestion)
                ->setName(self::decodeLabel($pkt, $offset));

            if ($rr->isQuestion()) {
                $values = unpack('ntype/nclass', substr($pkt, $offset, 4));
                $rr->setType($values['type'])->setClass($values['class']);
                $offset += 4;
            } else {
                $values = unpack('ntype/nclass/Nttl/ndlength', substr($pkt, $offset, 10));
                $rr->setType($values['type'])->setClass($values['class'])->setTtl($values['ttl']);
                $offset += 10;
                $rr->setRdata(self::decodeType($rr->getType(), substr($pkt, $offset, $values['dlength'])));
                $offset += $values['dlength'];
            }

            $resourceRecords[] = $rr;
        }

        return $resourceRecords;
    }

    /**
     * @param int $type
     * @param string $val
     * @return array|string
     * @throws UnsupportedTypeException
     */
    public static function decodeType($type, $val)
    {
        $offset = 0;

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                $data = inet_ntop($val);
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $data = self::decodeLabel($val, $offset);
                break;
            case RecordTypeEnum::TYPE_SOA:
                $data = array_merge(
                    [
                        'mname' => self::decodeLabel($val, $offset),
                        'rname' => self::decodeLabel($val, $offset),
                    ],
                    unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($val, $offset))
                );
                break;
            case RecordTypeEnum::TYPE_MX:
                $data = [
                    'preference' => unpack('npreference', $val)['preference'],
                    'exchange' => self::decodeLabel(substr($val, 2), $offset),
                ];
                break;
            case RecordTypeEnum::TYPE_TXT:
                $len = \ord($val[0]);

                if ((\strlen($val) + 1) < $len) {
                    $data = null;
                    break;
                }

                $data = substr($val, 1, $len);
                break;
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                $data = null;
                break;
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::get_name($type))
                );
        }

        return $data;
    }

    /**
     * @param $pkt
     * @param int $offset
     * @return Header
     */
    public static function decodeHeader($pkt, &$offset = 0): Header
    {
        $data = unpack('nid/nflags/nqdcount/nancount/nnscount/narcount', $pkt);
        $flags = self::decodeFlags($data['flags']);
        $offset += 12;

        return (new Header)
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
}
