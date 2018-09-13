<?php
/*
 * This file is part of PHP DNS Server.
 *
 * (c) Yif Swery <yiftachswr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace yswery\DNS;

class RecursiveResolver implements ResolverInterface
{
    private $recursionAvailable = true;

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
                'rdata' => $this->extractRdata($record),
                'ttl' => $record['ttl'],
            ];
        }

        return $result;
    }

    /**
     * @param array $array
     *
     * @return array|mixed
     *
     * @throws UnsupportedTypeException
     */
    private function extractRdata(array $array)
    {
        $type = RecordTypeEnum::getTypeIndex($array['type']);

        switch ($type) {
            case RecordTypeEnum::TYPE_A:
                $rdata = $array['ip'];
                break;
            case RecordTypeEnum::TYPE_AAAA:
                $rdata = $array['ipv6'];
                break;
            case RecordTypeEnum::TYPE_NS:
            case RecordTypeEnum::TYPE_CNAME:
            case RecordTypeEnum::TYPE_PTR:
                $rdata = $array['target'];
                break;
            case RecordTypeEnum::TYPE_SOA:
                $rdata = [
                        'mname' => $array['mname'],
                        'rname' => $array['rname'],
                        'serial' => $array['serial'],
                        'refresh' => $array['refresh'],
                        'retry' => $array['retry'],
                        'expire' => $array['expire'],
                        'minimum' => $array['minimum-ttl'],
                    ];
                break;
            case RecordTypeEnum::TYPE_MX:
                $rdata = [
                    'preference' => $array['pri'],
                    'exchange' => $array['host'],
                ];
                break;
            case RecordTypeEnum::TYPE_TXT:
                $rdata = $array['txt'];
                break;
            default:
                throw new UnsupportedTypeException(
                    sprintf('Record type "%s" is not a supported type.', RecordTypeEnum::getName($type))
                );
        }

        return $rdata;
    }

    /**
     * Maps an IANA Rdata type to the built-in PHP DNS constant.
     *
     * @example $this->IANA_to_PHP(5) //Returns DNS_CNAME int(16)
     *
     * @param int $type the IANA RTYPE
     *
     * @return int|bool the built in PHP DNS_<type> constant
     */
    private function IANA2PHP(int $type): int
    {
        $constantName = 'DNS_'.RecordTypeEnum::getName($type);

        return defined($constantName) ? constant($constantName) : false;
    }

    /**
     * Getter method for $recursion_available property.
     *
     * @return bool
     */
    public function allowsRecursion(): bool
    {
        return $this->recursionAvailable;
    }

    /**
     * Check if the resolver knows about a domain.
     *
     * @param string $domain the domain to check for
     *
     * @return bool true if the resolver holds info about $domain
     */
    public function isAuthority($domain): bool
    {
        return false;
    }
}
