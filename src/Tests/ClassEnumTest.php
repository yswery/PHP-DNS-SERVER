<?php

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
