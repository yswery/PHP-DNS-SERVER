<?php

namespace yswery\DNS\Tests;

use Symfony\Component\Yaml\Exception\ParseException;
use yswery\DNS\Resolver\YamlResolver;

class YamlResolverTest extends JsonResolverTest
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->storage = new YamlResolver(__DIR__.'/Resources/records.yaml');
    }

    /**
     * @throws \Exception
     */
    public function testParseException()
    {
        $this->expectException(ParseException::class);
        new YamlResolver(__DIR__.'/Resources/invalid_dns_records.json');
    }
}
