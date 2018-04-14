<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Resolver;

use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlResolver
 */
class YamlResolver extends JsonResolver
{
    /**
     * YamlResolver constructor.
     *
     * @param string $file The file path of the Yaml-formatted DNS Zone file.
     *
     * @throws \Exception
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new \Exception(sprintf('The file "%s" does not exist.', $file));
        }

        if (!is_int($this->ttl)) {
            throw new \InvalidArgumentException('Default TTL must be an integer.');
        }

        $this->records = Yaml::parseFile($file);
    }
}
