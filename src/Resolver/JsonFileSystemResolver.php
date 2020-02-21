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

use yswery\DNS\ClassEnum;
use yswery\DNS\Exception\ZoneFileNotFoundException;
use yswery\DNS\Filesystem\FilesystemManager;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

class JsonFileSystemResolver extends AbstractResolver
{
    /**
     * @var int
     */
    protected $defaultClass = ClassEnum::INTERNET;

    /**
     * @var FilesystemManager
     */
    protected $filesystemManager;

    /**
     * @var int
     */
    protected $defaultTtl;

    /**
     * JsonResolver constructor.
     *
     * @param FilesystemManager $filesystemManager
     * @param int               $defaultTtl
     *
     * @throws UnsupportedTypeException
     */
    public function __construct(FilesystemManager $filesystemManager, $defaultTtl = 300)
    {
        $this->isAuthoritative = true;
        $this->allowRecursion = false;
        $this->filesystemManager = $filesystemManager;
        $this->defaultTtl = $defaultTtl;

        $zones = glob($filesystemManager->zonePath().'/*.json');
        foreach ($zones as $file) {
            $zone = json_decode(file_get_contents($file), true);
            $resourceRecords = $this->isLegacyFormat($zone) ? $this->processLegacyZone($zone) : $this->processZone($zone);
            $this->addZone($resourceRecords);
        }
    }

    /**
     * Load a zone file.
     *
     * @param string $file
     *
     * @throws UnsupportedTypeException
     * @throws ZoneFileNotFoundException
     */
    public function loadZone($file)
    {
        if (file_exists($file)) {
            $zone = json_decode(file_get_contents($file), true);
            $resourceRecords = $this->isLegacyFormat($zone) ? $this->processLegacyZone($zone) : $this->processZone($zone);
            $this->addZone($resourceRecords);
        } else {
            throw new ZoneFileNotFoundException('The zone file could not be found');
        }
    }

    /**
     * Saves the zone to a .json file.
     *
     * @param string $zone
     */
    public function saveZone($zone)
    {
    }

    /**
     * @param array $zone
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    protected function processZone(array $zone): array
    {
        $parent = rtrim($zone['domain'], '.').'.';
        $defaultTtl = $zone['default-ttl'];
        $rrs = $zone['resource-records'];
        $resourceRecords = [];

        foreach ($rrs as $rr) {
            $name = $rr['name'] ?? $parent;
            $class = isset($rr['class']) ? ClassEnum::getClassFromName($rr['class']) : $this->defaultClass;

            $resourceRecords[] = (new ResourceRecord())
                ->setName($this->handleName($name, $parent))
                ->setClass($class)
                ->setType($type = RecordTypeEnum::getTypeFromName($rr['type']))
                ->setTtl($rr['ttl'] ?? $defaultTtl)
                ->setRdata($this->extractRdata($rr, $type, $parent));
        }

        return $resourceRecords;
    }

    /**
     * Determine if a $zone is in the legacy format.
     *
     * @param array $zone
     *
     * @return bool
     */
    protected function isLegacyFormat(array $zone): bool
    {
        $keys = array_map(function ($value) {
            return strtolower($value);
        }, array_keys($zone));

        return
            (false === array_search('domain', $keys, true)) ||
            (false === array_search('resource-records', $keys, true));
    }

    /**
     * @param array $zones
     *
     * @return array
     */
    protected function processLegacyZone(array $zones): array
    {
        $resourceRecords = [];
        foreach ($zones as $domain => $types) {
            $domain = rtrim($domain, '.').'.';
            foreach ($types as $type => $data) {
                $data = (array) $data;
                $type = RecordTypeEnum::getTypeFromName($type);
                foreach ($data as $rdata) {
                    $resourceRecords[] = (new ResourceRecord())
                        ->setName($domain)
                        ->setType($type)
                        ->setClass($this->defaultClass)
                        ->setTtl($this->defaultTtl)
                        ->setRdata($rdata);
                }
            }
        }

        return $resourceRecords;
    }
}
