<?php
/**
 * @package yswery\DNS
 */

namespace yswery\DNS\Resolver;

use \Exception;
use yswery\DNS\RecordTypeEnum;

/**
 * Class RecursiveResolver
 */
class RecursiveResolver implements ResolverInterface
{
    private $dnsAnswerNames = [
        'DNS_A' => 'ip',
        'DNS_AAAA' => 'ipv6',
        'DNS_CNAME' => 'target',
        'DNS_TXT' => 'txt',
        'DNS_MX' => 'target',
        'DNS_NS' => 'target',
        'DNS_SOA' => ['mname', 'rname', 'serial', 'retry', 'refresh', 'expire', 'minimum-ttl'],
        'DNS_PTR' => 'target',
    ];

    /**
     * @inheritdoc
     *
     * @param array $query
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAnswer(array $query)
    {
        $answer = [];

        $domain = $query[0]['qname'];

        $type = RecordTypeEnum::get_name($query[0]['qtype']);

        $records = $this->getRecordsRecursivly($domain, $type);
        foreach ($records as $record) {
            $answer[] = [
                'name' => $query[0]['qname'],
                'class' => $query[0]['qclass'],
                'ttl' => $record['ttl'],
                'data' => ['type' => $query[0]['qtype'], 'value' => $record['answer']],
            ];
        }

        return $answer;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function allowsRecursion()
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @param string $domain
     *
     * @return bool
     */
    public function isAuthority($domain)
    {
        return false;
    }

    /**
     * @param $domain
     * @param $type
     *
     * @return array
     *
     * @throws Exception
     */
    private function getRecordsRecursivly($domain, $type)
    {
        $result = [];
        $dnsConstName = $this->getDnsCostName($type);

        if (!$dnsConstName) {
            throw new \Exception('Unsupported dns type to query.');
        }

        $dnsAnswerName = $this->dnsAnswerNames[$dnsConstName];
        $records = dns_get_record($domain, constant($dnsConstName));

        foreach ($records as $record) {
            if (is_array($dnsAnswerName)) {
                foreach ($dnsAnswerName as $name) {
                    $answer[$name] = $record[$name];
                }
            } else {
                $answer = $record[$dnsAnswerName];
            }
            $result[] = ['answer' => $answer, 'ttl' => $record['ttl']];
        }

        return $result;
    }

    /**
     * @param $type
     *
     * @return null|string
     */
    private function getDnsCostName($type)
    {
        $constName = "DNS_".strtoupper($type);
        $name = defined($constName) ? $constName : null;

        return $name;
    }
}
