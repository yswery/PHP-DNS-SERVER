<?php

namespace yswery\DNS;

class RecordTypeEnum {
    
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
    
    /**
     * @param int $typeIndex    The index of the type contained in the question
     * @return string|false
     */
    public static function get_name($typeIndex)
    {
        return array_search($typeIndex, self::$types);
    }
    
    /**
     * @param string $name      The name of the record type, e.g. = 'A' or 'MX' or 'SOA'
     * @return int|false
     */
    public static function get_type_index($name)
    {
        $key = trim(strtoupper($name));
        if(!array_key_exists($key, self::$types)) return false;
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
