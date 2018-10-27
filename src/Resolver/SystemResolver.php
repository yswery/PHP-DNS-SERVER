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

use yswery\DNS\UnsupportedTypeException;
use yswery\DNS\ResourceRecord;
use yswery\DNS\RecordTypeEnum;

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
     * @param ResourceRecord[] $question
     *
     * @return ResourceRecord[]
     *
     * @throws UnsupportedTypeException
     */
    public function getAnswer(array $question): array
    {
        $answer = [];
        $query = $question[0];

        $records = $this->getRecordsRecursively($query->getName(), $query->getType());
        foreach ($records as $record) {
            $answer[] = (new ResourceRecord())
                ->setName($query->getName())
                ->setClass($query->getClass())
                ->setTtl($record['ttl'])
                ->setRdata($record['rdata'])
                ->setType($query->getType());
        }

        return $answer;
    }

    /**
     * @param $domain
     * @param $type
     *
     * @return array
     *
     * @throws UnsupportedTypeException
     */
    private function getRecordsRecursively($domain, $type): array
    {
        if (false === $php_dns_type = $this->IANA2PHP($type)) {
            throw new UnsupportedTypeException(sprintf('Record type "%s" is not a supported type.', $type));
        }

        $records = dns_get_record($domain, $php_dns_type);
        $result = [];

        foreach ($records as $record) {
            $result[] = [
                'rdata' => $this->extractPhpRdata($record),
                'ttl' => $record['ttl'],
            ];
        }

        return $result;
    }

    /**
     * @param array  $resourceRecord
     * @param int    $type
     * @param string $parent
     *
     * @return array|mixed
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
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }
    }

    /**
     * Maps an IANA Rdata type to the built-in PHP DNS constant.
     *
     * @example $this->IANA_to_PHP(5) //Returns DNS_CNAME int(16)
     *
     * @param int $type the IANA RTYPE
     *
     * @return int|bool the built-in PHP DNS_<type> constant or `false` if the type is not defined
     */
    private function IANA2PHP(int $type)
    {
        $constantName = 'DNS_'.RecordTypeEnum::getName($type);

        return defined($constantName) ? constant($constantName) : false;
    }
}
