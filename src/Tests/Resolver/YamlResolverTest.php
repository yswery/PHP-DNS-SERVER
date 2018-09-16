<?php

namespace yswery\DNS\Tests\Resolver;

use Symfony\Component\Yaml\Exception\ParseException;
use yswery\DNS\Resolver\YamlResolver;

class YamlResolverTest extends JsonResolverTest
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $files = [
            __DIR__.'/../Resources/records.yml',
            __DIR__.'/../Resources/example.com.yml',
        ];
        $this->resolver = new YamlResolver($files);
    }

    /**
     * @throws \Exception
     */
    public function testParseException()
    {
        $this->expectException(ParseException::class);
        new YamlResolver([__DIR__.'/../Resources/invalid_dns_records.json']);
    }
}
