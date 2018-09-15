<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS\Resolver;

use Symfony\Component\Yaml\Yaml;

/**
 * Store dns records in yaml files.
 */
class YamlResolver extends JsonResolver
{
    /**
     * YamlResolver constructor.
     *
     * @param string $file
     * @param int    $ttl
     */
    public function __construct(string $file, $ttl = 300)
    {
        $this->ttl = $ttl;
        $this->records = Yaml::parseFile($file);
        $this->recursionAvailable = false;
    }
}
