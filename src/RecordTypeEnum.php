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

class RecordTypeEnum
{
    /**
     * @var array
     */
    private static $types = [
        'A' => 1,
        'NS' => 2,
        'CNAME' => 5,
        'SOA' => 6,
        'PTR' => 12,
        'MX' => 15,
        'TXT' => 16,
        'AAAA' => 28,
        'OPT' => 41,
        'AXFR' => 252,
        'ANY' => 255,
        'AFSDB' => 18,
        'APL' => 42,
        'CAA' => 257,
        'CDNSKEY' => 60,
        'CDS' => 59,
        'CERT' => 37,
        'DHCID' => 49,
        'DLV' => 32769,
        'DNSKEY' => 48,
        'DS' => 43,
        'IPSECKEY' => 45,
        'KEY' => 25,
        'KX' => 36,
        'LOC' => 29,
        'NAPTR' => 35,
        'NSEC' => 47,
        'NSEC3' => 50,
        'NSEC3PARAM' => 51,
        'RRSIG' => 46,
        'RP' => 17,
        'SIG' => 24,
        'SRV' => 33,
        'SSHFP' => 44,
        'TA' => 32768,
        'TKEY' => 249,
        'TLSA' => 52,
        'TSIG' => 250,
        'URI' => 256,
        'DNAME' => 39,
    ];

    public const TYPE_A = 1;
    public const TYPE_NS = 2;
    public const TYPE_CNAME = 5;
    public const TYPE_SOA = 6;
    public const TYPE_PTR = 12;
    public const TYPE_MX = 15;
    public const TYPE_TXT = 16;
    public const TYPE_AAAA = 28;
    public const TYPE_OPT = 41;
    public const TYPE_AXFR = 252;
    public const TYPE_ANY = 255;
    public const TYPE_AFSDB = 18;
    public const TYPE_APL = 42;
    public const TYPE_CAA = 257;
    public const TYPE_CDNSKEY = 60;
    public const TYPE_CDS = 59;
    public const TYPE_CERT = 37;
    public const TYPE_DHCID = 49;
    public const TYPE_DLV = 32769;
    public const TYPE_DNSKEY = 48;
    public const TYPE_DS = 43;
    public const TYPE_IPSECKEY = 45;
    public const TYPE_KEY = 25;
    public const TYPE_KX = 36;
    public const TYPE_LOC = 29;
    public const TYPE_NAPTR = 35;
    public const TYPE_NSEC = 47;
    public const TYPE_NSEC3 = 50;
    public const TYPE_NSEC3PARAM = 51;
    public const TYPE_RRSIG = 46;
    public const TYPE_RP = 17;
    public const TYPE_SIG = 24;
    public const TYPE_SRV = 33;
    public const TYPE_SSHFP = 44;
    public const TYPE_TA = 32768;
    public const TYPE_TKEY = 249;
    public const TYPE_TLSA = 52;
    public const TYPE_TSIG = 250;
    public const TYPE_URI = 256;
    public const TYPE_DNAME = 39;

    /**
     * @param int $index The index of the type contained in the question
     *
     * @return string|false
     */
    public static function getName($index): string
    {
        return array_search($index, self::$types, true);
    }

    /**
     * @param string $name The name of the record type, e.g. = 'A' or 'MX' or 'SOA'
     * @return int|false
     */
    public static function getTypeIndex(string $name): int
    {
        $key = strtoupper(trim($name));
        if (!array_key_exists($key, self::$types)) {
            return false;
        }
        return self::$types[$key];
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return self::$types;
    }
}
