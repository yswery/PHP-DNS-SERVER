<?php

namespace yswery\DNS\Tests\Resolver;

use yswery\DNS\Resolver\XmlResolver;

class XmlResolverTest extends JsonResolverTest
{
    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/example.com.xml',
            __DIR__.'/../Resources/test.com.xml',
            __DIR__.'/../Resources/test2.com.xml',
        ];
        $this->resolver = new XmlResolver($files);
    }

    public function testResolveLegacyRecord()
    {
        $this->markTestSkipped();
    }
}
