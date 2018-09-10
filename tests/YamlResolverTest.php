<?php

namespace yswery\DNS\Tests;

use yswery\DNS\YamlResolver;

class YamlResolverTest extends JsonResolverTest
{
    public function setUp()
    {
        $this->storage = new YamlResolver(__DIR__.'/test_records.yaml');
    }
}
