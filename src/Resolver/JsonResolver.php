<?php

namespace yswery\DNS\Resolver;

use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

class JsonResolver extends AbstractResolver
{
    /**
     * @var string
     */
    protected $defaultClass = ClassEnum::INTERNET;

    /**
     * @var int
     */
    protected $defaultTtl;

    /**
     * EnhancedJsonResolver constructor.
     *
     * @param array $files
     * @param int   $defaultTtl
     *
     * @throws UnsupportedTypeException
     */
    public function __construct(array $files, $defaultTtl = 300)
    {
        $this->isAuthoritative = true;
        $this->allowRecursion = false;
        $this->defaultTtl = $defaultTtl;

        foreach ($files as $file) {
            $this->addZone(json_decode(file_get_contents($file), true));
        }
    }

    /**
     * @param array $zone
     *
     * @throws UnsupportedTypeException
     */
    protected function addZone(array $zone): void
    {
        $resourceRecords = $this->isLegacyFormat($zone) ? $this->processLegacyZone($zone) : $this->processZone($zone);
        foreach ($resourceRecords as $resourceRecord) {
            $this->resourceRecords[$resourceRecord->getName()][$resourceRecord->getType()][$resourceRecord->getClass()][] = $resourceRecord;
        }
    }

    /**
     * @param array $zone
     *
     * @return \Generator|ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    private function processZone(array $zone)
    {
        $parent = rtrim($zone['domain'], '.').'.';
        $defaultTtl = $zone['default-ttl'];
        $rrs = $zone['resource-records'];

        foreach ($rrs as $rr) {
            $name = $rr['name'] ?? $parent;
            $class = isset($rr['class']) ? ClassEnum::getClassFromName($rr['class']) : $this->defaultClass;

            yield (new ResourceRecord())
                ->setName($this->handleName($name, $parent))
                ->setClass($class)
                ->setType(RecordTypeEnum::getTypeIndex($rr['type']))
                ->setTtl($rr['ttl'] ?? $defaultTtl)
                ->setRdata($this->extractRdata($rr, $parent));
        }
    }

    /**
     * Determine if a $zone is in the legacy format.
     *
     * @param array $zone
     *
     * @return bool
     */
    private function isLegacyFormat(array $zone): bool
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
     * @return \Generator|ResourceRecord[]
     */
    private function processLegacyZone(array $zones)
    {
        foreach ($zones as $domain => $types) {
            $domain = rtrim($domain, '.').'.';
            foreach ($types as $type => $data) {
                $data = (array) $data;
                $type = RecordTypeEnum::getTypeIndex($type);
                foreach ($data as $rdata) {
                    yield (new ResourceRecord())
                        ->setName($domain)
                        ->setType($type)
                        ->setClass($this->defaultClass)
                        ->setTtl($this->defaultTtl)
                        ->setRdata($rdata);
                }
            }
        }
    }

    /**
     * Add the parent domain to names that are not fully qualified.
     *
     * EnhancedJsonResolver::handleName('www', 'example.com.') //Outputs 'www.example.com.'
     *
     * @param $name
     * @param $parent
     *
     * @return string
     */
    private function handleName($name, $parent)
    {
        if ('@' === $name || '' === $name) {
            return $parent;
        }

        if ('.' !== substr($name, -1, 1)) {
            return $name.'.'.$parent;
        }

        return $name;
    }

    /**
     * @param array  $resourceRecord
     * @param string $parent
     *
     * @throws UnsupportedTypeException
     *
     * @return array|string
     */
    private function extractRdata(array $resourceRecord, string $parent)
    {
        switch (RecordTypeEnum::getTypeIndex($resourceRecord['type'])) {
            case RecordTypeEnum::TYPE_A:
            case RecordTypeEnum::TYPE_AAAA:
                return $resourceRecord['address'];
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                return $this->handleName($resourceRecord['target'], $parent);
            case RecordTypeEnum::TYPE_SOA:
                return [
                    'mname' => $this->handleName($resourceRecord['mname'], $parent),
                    'rname' => $this->handleName($resourceRecord['rname'], $parent),
                    'serial' => $resourceRecord['serial'],
                    'refresh' => $resourceRecord['refresh'],
                    'retry' => $resourceRecord['retry'],
                    'expire' => $resourceRecord['expire'],
                    'minimum' => $resourceRecord['minimum'],
                ];
            case RecordTypeEnum::TYPE_MX:
                return [
                    'preference' => $resourceRecord['preference'],
                    'exchange' => $this->handleName($resourceRecord['exchange'], $parent),
                ];
            case RecordTypeEnum::TYPE_TXT:
                return $resourceRecord['text'];
            case RecordTypeEnum::TYPE_AXFR:
            case RecordTypeEnum::TYPE_ANY:
                return '';
            default:
                throw new UnsupportedTypeException(
                    sprintf('Resource Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }
    }
}
