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
use yswery\DNS\RecordTypeEnum;

class RecordTypeEnumTest extends TestCase
{
    public function testIsValid()
    {
        $this->assertTrue(RecordTypeEnum::isValid(1));
        $this->assertFalse(RecordTypeEnum::isValid(3));
    }

    public function testGetName()
    {
        $this->assertEquals('MX', RecordTypeEnum::getName(RecordTypeEnum::TYPE_MX));
        $this->expectException(\InvalidArgumentException::class);
        RecordTypeEnum::getName(651);
    }

    public function testGetTypeFromName()
    {
        $this->assertEquals(15, RecordTypeEnum::getTypeFromName('MX'));
        $this->assertEquals(6, RecordTypeEnum::getTypeFromName('soa'));

        $this->expectException(\InvalidArgumentException::class);
        RecordTypeEnum::getTypeFromName('NONE');
    }

    public function testGetTypes()
    {
        $this->assertTrue(is_array(RecordTypeEnum::getTypes()));
    }
}
