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
    public const INTERNET = 1;
    public const CSNET = 2;
    public const CHAOS = 3;
    public const HESIOD = 4;

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
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function getName(int $class): string
    {
        if (!static::isValid($class)) {
            throw new \InvalidArgumentException(sprintf('No class matching integer "%s"', $class));
        }

        return self::$classes[$class];
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public static function getClassFromName(string $name): int
    {
        $class = array_search(strtoupper($name), self::$classes, true);

        if (false === $class || !is_int($class)) {
            throw new \InvalidArgumentException(sprintf('Class: "%s" is not defined.', $name));
        }

        return $class;
    }
}
