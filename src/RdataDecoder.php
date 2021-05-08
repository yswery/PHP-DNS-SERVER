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

class RdataDecoder
{
    /**
     * Maps an RData type to its decoder method.
     *
     * @var array
     */
    private static $methodMap = [
        RecordTypeEnum::TYPE_A => 'a',
        RecordTypeEnum::TYPE_AAAA => 'a',
        RecordTypeEnum::TYPE_CNAME => 'cname',
        RecordTypeEnum::TYPE_DNAME => 'cname',
        RecordTypeEnum::TYPE_NS => 'cname',
        RecordTypeEnum::TYPE_PTR => 'cname',
        RecordTypeEnum::TYPE_SOA => 'soa',
        RecordTypeEnum::TYPE_MX => 'mx',
        RecordTypeEnum::TYPE_TXT => 'txt',
        RecordTypeEnum::TYPE_SRV => 'srv',
    ];

    /**
     * @param int    $type
     * @param string $rdata
     *
     * @return array|string
     *
     * @throws UnsupportedTypeException
     */
    public static function decodeRdata(int $type, string $rdata)
    {
        if (!array_key_exists($type, self::$methodMap)) {
            throw new UnsupportedTypeException(sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type)));
        }

        return call_user_func(['self', self::$methodMap[$type]], $rdata);
    }

    /**
     * Used for A and AAAA records.
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function a(string $rdata): string
    {
        return inet_ntop($rdata);
    }

    /**
     * Used for CNAME, DNAME, NS, and PTR records.
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function cname(string $rdata): string
    {
        return Decoder::decodeDomainName($rdata);
    }

    /**
     * Exclusively for SOA records.
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function soa(string $rdata): array
    {
        $offset = 0;

        return array_merge(
            [
                'mname' => Decoder::decodeDomainName($rdata, $offset),
                'rname' => Decoder::decodeDomainName($rdata, $offset),
            ],
            unpack('Nserial/Nrefresh/Nretry/Nexpire/Nminimum', substr($rdata, $offset))
        );
    }

    /**
     * Exclusively for MX records.
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function mx(string $rdata): array
    {
        return [
            'preference' => unpack('npreference', $rdata)['preference'],
            'exchange' => Decoder::decodeDomainName(substr($rdata, 2)),
        ];
    }

    /**
     * Exclusively for TXT records.
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function txt(string $rdata): string
    {
        $len = ord($rdata[0]);
        if ((strlen($rdata) + 1) < $len) {
            return '';
        }

        return substr($rdata, 1, $len);
    }

    /**
     * Exclusively for SRV records.
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function srv(string $rdata): array
    {
        $offset = 6;
        $values = unpack('npriority/nweight/nport', $rdata);
        $values['target'] = Decoder::decodeDomainName($rdata, $offset);

        return $values;
    }
}
