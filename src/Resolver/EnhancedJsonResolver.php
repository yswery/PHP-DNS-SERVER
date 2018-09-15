<?php

namespace yswery\DNS\Resolver;


use yswery\DNS\ClassEnum;
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

class EnhancedJsonResolver extends AbstractResolver
{
    /**
     * @var string
     */
    private $defaultClass = 'IN';

    /**
     * EnhancedJsonResolver constructor.
     * @param array $files
     * @throws UnsupportedTypeException
     */
    public function __construct(array $files)
    {
        $this->isAuthoritative = true;
        $this->allowRecursion = false;

        foreach ($files as $file) {
            $this->addZone(json_decode(file_get_contents($file), true));
        }
    }

    /**
     * @param array $zone
     * @throws UnsupportedTypeException
     */
    private function addZone(array $zone): void
    {
        foreach ($this->processZone($zone) as $resourceRecord) {
            $this->resourceRecords[$resourceRecord->getName()][$resourceRecord->getType()][$resourceRecord->getClass()][] = $resourceRecord;
        }
    }

    /**
     * @param array $zone
     * @return \Generator
     * @throws UnsupportedTypeException
     */
    private function processZone(array $zone)
    {
        $parent = $zone['domain'];
        $defaultTtl = $zone['default-ttl'];
        $rrs = $zone['resource-records'];

        foreach ($rrs as $rr) {
            $name = $rr['name'] ?? $parent;

            yield (new ResourceRecord)
                ->setName($this->handleName($name, $parent))
                ->setClass(ClassEnum::getClassFromName($rr['class'] ?? $this->defaultClass))
                ->setType($type = RecordTypeEnum::getTypeIndex($rr['type']))
                ->setTtl($rr['ttl'] ?? $defaultTtl)
                ->setRdata($this->extractRdata($rr, $type, $parent));
        }
    }

    /**
     * Add the parent domain to names that are not fully qualified.
     *
     * EnhancedJsonResolver::handleName('www', 'example.com.') //Outputs 'www.example.com.'
     *
     * @param $name
     * @param $parent
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
     * @param array $resourceRecord
     * @param int $type
     * @param string $parent
     * @throws UnsupportedTypeException
     * @return array|string
     */
    private function extractRdata(array $resourceRecord, int $type, string $parent)
    {
        switch ($type) {
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