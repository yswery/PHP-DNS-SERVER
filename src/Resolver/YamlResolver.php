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
use yswery\DNS\Tests\Resolver\JsonResolverTest;
use yswery\DNS\UnsupportedTypeException;

/**
 * Store dns records in yaml files.
 */
class YamlResolver extends JsonResolver
{
    /**
     * YamlResolver constructor.
     *
     * @param array $files
     * @param int   $defaultTtl
     *
     * @throws UnsupportedTypeException
     */
    public function __construct(array $files, $defaultTtl = 300)
    {
        parent::__construct([], $defaultTtl);

        foreach ($files as $file) {
            $zone = Yaml::parseFile($file);
            $resourceRecords = $this->isLegacyFormat($zone) ? $this->processLegacyZone($zone) : $this->processZone($zone);
            $this->addZone($resourceRecords);
        }
    }
}
