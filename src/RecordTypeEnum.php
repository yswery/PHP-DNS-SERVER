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
    private static $names = [
        self::TYPE_A => 'A',
        self::TYPE_NS => 'NS',
        self::TYPE_CNAME => 'CNAME',
        self::TYPE_SOA => 'SOA',
        self::TYPE_PTR => 'PTR',
        self::TYPE_MX => 'MX',
        self::TYPE_TXT => 'TXT',
        self::TYPE_AAAA => 'AAAA',
        self::TYPE_OPT => 'OPT',
        self::TYPE_AXFR => 'AXFR',
        self::TYPE_ANY => 'ANY',
        self::TYPE_AFSDB => 'AFSDB',
        self::TYPE_APL => 'APL',
        self::TYPE_CAA => 'CAA',
        self::TYPE_CDNSKEY => 'CDNSKEY',
        self::TYPE_CDS => 'CDS',
        self::TYPE_CERT => 'CERT',
        self::TYPE_DHCID => 'DHCID',
        self::TYPE_DLV => 'DLV',
        self::TYPE_DNSKEY => 'DNSKEY',
        self::TYPE_DS => 'DS',
        self::TYPE_IPSECKEY => 'IPSECKEY',
        self::TYPE_KEY => 'KEY',
        self::TYPE_KX => 'KX',
        self::TYPE_LOC => 'LOC',
        self::TYPE_NAPTR => 'NAPTR',
        self::TYPE_NSEC => 'NSEC',
        self::TYPE_NSEC3 => 'NSEC3',
        self::TYPE_NSEC3PARAM => 'NSEC3PARAM',
        self::TYPE_RRSIG => 'RRSIG',
        self::TYPE_RP => 'RP',
        self::TYPE_SIG => 'SIG',
        self::TYPE_SRV => 'SRV',
        self::TYPE_SSHFP => 'SSHFP',
        self::TYPE_TA => 'TA',
        self::TYPE_TKEY => 'TKEY',
        self::TYPE_TLSA => 'TLSA',
        self::TYPE_TSIG => 'TSIG',
        self::TYPE_URI => 'URI',
        self::TYPE_DNAME => 'DNAME',
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
     * @param int $type
     *
     * @return bool
     */
    public static function isValid(int $type): bool
    {
        return array_key_exists($type, self::$names);
    }

    /**
     * Get the name of an RDATA type. E.g. RecordTypeEnum::getName(6) return 'SOA'.
     *
     * @param int $type The index of the type
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function getName(int $type): string
    {
        if (!self::isValid($type)) {
            throw new \InvalidArgumentException(sprintf('The integer "%d" does not correspond to a valid type', $type));
        }

        return self::$names[$type];
    }

    /**
     * Return the integer value of an RDATA type. E.g. getTypeFromName('MX') returns 15.
     *
     * @param string $name The name of the record type, e.g. = 'A' or 'MX' or 'SOA'
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public static function getTypeFromName(string $name): int
    {
        $type = array_search(strtoupper(trim($name)), self::$names);
        if (false === $type || !is_int($type)) {
            throw new \InvalidArgumentException(sprintf('RData type "%s" is not defined.', $name));
        }

        return $type;
    }

    /**
     * @return array An array of all valid RDATA types
     */
    public static function getTypes(): array
    {
        return self::$names;
    }
}
