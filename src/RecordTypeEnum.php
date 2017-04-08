<?php

namespace yswery\DNS;

class RecordTypeEnum
{

    /**
     * @var array
     */
    private static $types = array(
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
    );

    const TYPE_A = 1;
    const TYPE_NS = 2;
    const TYPE_CNAME = 5;
    const TYPE_SOA = 6;
    const TYPE_PTR = 12;
    const TYPE_MX = 15;
    const TYPE_TXT = 16;
    const TYPE_AAAA = 28;
    const TYPE_OPT = 41;
    const TYPE_AXFR = 252;
    const TYPE_ANY = 255;
    const TYPE_AFSDB = 18;
    const TYPE_APL = 42;
    const TYPE_CAA = 257;
    const TYPE_CDNSKEY = 60;
    const TYPE_CDS = 59;
    const TYPE_CERT = 37;
    const TYPE_DHCID = 49;
    const TYPE_DLV = 32769;
    const TYPE_DNSKEY = 48;
    const TYPE_DS = 43;
    const TYPE_IPSECKEY = 45;
    const TYPE_KEY = 25;
    const TYPE_KX = 36;
    const TYPE_LOC = 29;
    const TYPE_NAPTR = 35;
    const TYPE_NSEC = 47;
    const TYPE_NSEC3 = 50;
    const TYPE_NSEC3PARAM = 51;
    const TYPE_RRSIG = 46;
    const TYPE_RP = 17;
    const TYPE_SIG = 24;
    const TYPE_SRV = 33;
    const TYPE_SSHFP = 44;
    const TYPE_TA = 32768;
    const TYPE_TKEY = 249;
    const TYPE_TLSA = 52;
    const TYPE_TSIG = 250;
    const TYPE_URI = 256;
    const TYPE_DNAME = 39;

    /**
     * @param int $typeIndex The index of the type contained in the question
     * @return string|false
     */
    public static function get_name($typeIndex)
    {
        return array_search($typeIndex, self::$types);
    }

    /**
     * @param string $name The name of the record type, e.g. = 'A' or 'MX' or 'SOA'
     * @return int|false
     */
    public static function get_type_index($name)
    {
        $key = trim(strtoupper($name));
        if (!array_key_exists($key, self::$types)) {
            return false;
        }
        return self::$types[$key];
    }

    /**
     * @return array
     */
    public static function get_types()
    {
        return self::$types;
    }
}
