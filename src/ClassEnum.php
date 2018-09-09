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

class ClassEnum
{
    const INTERNET = 1;
    const CSNET = 2;
    const CHAOS = 3;
    const HESIOD = 4;

    /**
     * @var array
     */
    public static $classes = [
        self::INTERNET => 'IN',
        self::CSNET => 'CS',
        self::CHAOS => 'CHAOS',
        self::HESIOD => 'HS',
    ];

    /**
     * Determine if a class is valid.
     *
     * @param string $class
     *
     * @return bool
     */
    public static function isValid($class): bool
    {
        return array_key_exists($class, self::$classes);
    }

    /**
     * @param int $class
     * @return mixed
     */
    public static function getName(int $class): string
    {
        return self::$classes[$class];
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getClassFromName(string $name): int
    {
        return array_search($name, self::$classes);
    }
}
