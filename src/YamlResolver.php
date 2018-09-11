<?php

namespace yswery\DNS;

use Symfony\Component\Yaml\Yaml;

class YamlResolver extends JsonResolver
{
    public function __construct(string $file, $ttl = 300)
    {
        $this->DS_TTL = $ttl;
        $this->dns_records = Yaml::parseFile($file);
        $this->recursion_available = false;
    }
}
