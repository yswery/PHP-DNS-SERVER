<?php

namespace yswery\DNS\Tests\Resolver;

use yswery\DNS\Resolver\JsonResolver;
use yswery\DNS\Resolver\StackableResolver;
use yswery\DNS\Resolver\XmlResolver;
use yswery\DNS\Resolver\YamlResolver;

class StackableResolverTest extends JsonResolverTest
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $jsonFiles = [
            __DIR__.'/../Resources/example.com.json',
            __DIR__.'/../Resources/test_records.json',
        ];

        $xmlFiles = [
            __DIR__.'/../Resources/example.com.xml',
            __DIR__.'/../Resources/test.com.xml',
            __DIR__.'/../Resources/test2.com.xml',
        ];

        $ymlFiles = [
            __DIR__.'/../Resources/records.yml',
            __DIR__.'/../Resources/example.com.yml',
        ];

        $this->resolver = new StackableResolver([
            new JsonResolver($jsonFiles),
            new XmlResolver($xmlFiles),
            new YamlResolver($ymlFiles),
        ]);
    }
}
