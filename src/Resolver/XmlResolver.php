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
use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

class XmlResolver extends AbstractResolver
{
    private $defaultClass = ClassEnum::INTERNET;

    /**
     * XmlResolver constructor.
     *
     * @param array $files
     *
     * @throws UnsupportedTypeException
     */
    public function __construct(array $files)
    {
        $this->isAuthoritative = true;
        $this->allowRecursion = false;

        foreach ($files as $file) {
            $xml = new \SimpleXMLElement(file_get_contents($file));
            $this->addZone($this->process($xml));
        }
    }

    /**
     * @param object $xml
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    private function process($xml): array
    {
        $parent = (string) $xml->{'name'};
        $defaultTtl = (int) $xml->{'default-ttl'};
        $resourceRecords = [];

        foreach ($xml->{'resource-records'}->{'resource-record'} as $rr) {
            $name = (string) $rr->{'name'} ?? $parent;
            $class = isset($rr->{'class'}) ? ClassEnum::getClassFromName($rr->{'class'}) : $this->defaultClass;
            $ttl = isset($rr->{'ttl'}) ? (int) $rr->{'ttl'} : $defaultTtl;

            $resourceRecords[] = (new ResourceRecord())
                ->setName($this->handleName($name, $parent))
                ->setClass($class)
                ->setType($type = RecordTypeEnum::getTypeFromName($rr->{'type'}))
                ->setTtl($ttl)
                ->setRdata($this->extractRdata($this->simpleXmlToArray($rr->{'rdata'}), $type, $parent));
        }

        return $resourceRecords;
    }

    /**
     * Convert a SimpleXML object to an associative array.
     *
     * @param \SimpleXMLElement $xmlObject
     *
     * @return array
     */
    private function simpleXmlToArray(\SimpleXMLElement $xmlObject): array
    {
        $array = [];
        foreach ($xmlObject->children() as $node) {
            $array[$node->getName()] = is_array($node) ? $this->simpleXmlToArray($node) : (string) $node;
        }

        return $array;
    }
}
