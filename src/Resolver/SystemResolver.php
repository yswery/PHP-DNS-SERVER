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

use yswery\DNS\RecordTypeEnum;
use yswery\DNS\ResourceRecord;
use yswery\DNS\UnsupportedTypeException;

/**
 * Use the host system's configured DNS.
 */
class SystemResolver extends AbstractResolver
{
    /**
     * SystemResolver constructor.
     *
     * @param bool $recursionAvailable
     * @param bool $authoritative
     */
    public function __construct($recursionAvailable = true, $authoritative = false)
    {
        $this->allowRecursion = (bool) $recursionAvailable;
        $this->isAuthoritative = (bool) $authoritative;
    }

    /**
     * @param ResourceRecord[] $queries
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    public function getAnswer(array $queries, ?string $client = null): array
    {
        $answer = [];
        foreach ($queries as $query) {
            $answer = array_merge($answer, $this->getRecordsRecursively($query));
        }

        return $answer;
    }

    /**
     * Resolve the $query using the system configured local DNS.
     *
     * @param ResourceRecord $query
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    private function getRecordsRecursively(ResourceRecord $query): array
    {
        $records = dns_get_record($query->getName(), $this->IANA2PHP($query->getType()));
        $result = [];

        foreach ($records as $record) {
            $result[] = (new ResourceRecord())
                ->setName($query->getName())
                ->setClass($query->getClass())
                ->setTtl($record['ttl'])
                ->setRdata($this->extractPhpRdata($record))
                ->setType($query->getType());
        }

        return $result;
    }

    /**
     * @param array $resourceRecord
     *
     * @return array|string
     *
     * @throws UnsupportedTypeException
     */
    protected function extractPhpRdata(array $resourceRecord)
    {
        $type = RecordTypeEnum::getTypeFromName($resourceRecord['type']);

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
                return $resourceRecord['ip'];
            case RecordTypeEnum::TYPE_AAAA:
                return $resourceRecord['ipv6'];
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                return $resourceRecord['target'];
            case RecordTypeEnum::TYPE_SOA:
                return [
                        'mname' => $resourceRecord['mname'],
                        'rname' => $resourceRecord['rname'],
                        'serial' => $resourceRecord['serial'],
                        'refresh' => $resourceRecord['refresh'],
                        'retry' => $resourceRecord['retry'],
                        'expire' => $resourceRecord['expire'],
                        'minimum' => $resourceRecord['minimum-ttl'],
                    ];
            case RecordTypeEnum::TYPE_MX:
                return [
                    'preference' => $resourceRecord['pri'],
                    'exchange' => $resourceRecord['host'],
                ];
            case RecordTypeEnum::TYPE_TXT:
                return $resourceRecord['txt'];
            case RecordTypeEnum::TYPE_SRV:
                return [
                    'priority' => $resourceRecord['pri'],
                    'port' => $resourceRecord['port'],
                    'weight' => $resourceRecord['weight'],
                    'target' => $resourceRecord['target'],
                ];
            default:
                throw new UnsupportedTypeException(sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type)));
        }
    }

    /**
     * Maps an IANA Rdata type to the built-in PHP DNS constant.
     *
     * @example $this->IANA_to_PHP(5) //Returns DNS_CNAME int(16)
     *
     * @param int $type the IANA RTYPE
     *
     * @return int the built-in PHP DNS_<type> constant or `false` if the type is not defined
     *
     * @throws UnsupportedTypeException|\InvalidArgumentException
     */
    private function IANA2PHP(int $type): int
    {
        $constantName = 'DNS_'.RecordTypeEnum::getName($type);
        if (!defined($constantName)) {
            throw new UnsupportedTypeException(sprintf('Record type "%d" is not a supported type.', $type));
        }

        $phpType = constant($constantName);

        if (!is_int($phpType)) {
            throw new \InvalidArgumentException(sprintf('Constant "%s" is not an integer.', $constantName));
        }

        return $phpType;
    }
}
