<?php

/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Tests;

use PHPUnit\Framework\TestCase;
use yswery\DNS\ClassEnum;

class ClassEnumTest extends TestCase
{
    public function testGetClassFromName()
    {
        $this->assertEquals(ClassEnum::HESIOD, ClassEnum::getClassFromName('HS'));
        $this->assertEquals(ClassEnum::CHAOS, ClassEnum::getClassFromName('chaos'));
        $this->expectException(\InvalidArgumentException::class);
        ClassEnum::getClassFromName('NO');
    }
}
