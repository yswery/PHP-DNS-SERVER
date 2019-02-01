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
     */
    public static function decodeMessage(string $message): Message
    {
        $offset = 0;
        $header = self::decodeHeader($message, $offset);
        $messageObject = new Message($header);
        $messageObject->setQuestions(self::decodeResourceRecords($message, $header->getQuestionCount(), $offset, true));
        $messageObject->setAnswers(self::decodeResourceRecords($message, $header->getAnswerCount(), $offset));
        $messageObject->setAuthoritatives(self::decodeResourceRecords($message, $header->getNameServerCount(), $offset));
        $messageObject->setAdditionals(self::decodeResourceRecords($message, $header->getAdditionalRecordsCount(), $offset));

        return $messageObject;
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

                //Ignore unsupported types.
                try {
                    $rr->setRdata(RdataDecoder::decodeRdata($rr->getType(), substr($pkt, $offset, $values['dlength'])));
                } catch (UnsupportedTypeException $e) {
                    $offset += $values['dlength'];
                    continue;
                }
                $offset += $values['dlength'];
            }

            $resourceRecords[] = $rr;
        }

        return $resourceRecords;
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
