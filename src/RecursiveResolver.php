<?php

namespace yswery\DNS;

use \Exception;

class RecursiveResolver implements ResolverInterface
{
    private $recursion_available = true;

    private $dns_answer_names = [
        'DNS_A' => 'ip',
        'DNS_AAAA' => 'ipv6',
        'DNS_CNAME' => 'target',
        'DNS_TXT' => 'txt',
        'DNS_MX' => ['exchange', 'preference'],
        'DNS_NS' => 'target',
        'DNS_SOA' => ['mname', 'rname', 'serial', 'retry', 'refresh', 'expire', 'minimum'],
        'DNS_PTR' => 'target',
    ];

    public function getAnswer(array $question): array
    {
        $answer = [];

        $domain = $question[0]['name'];

        $type = RecordTypeEnum::get_name($question[0]['qtype']);

        $records = $this->get_records_recursivly($domain, $type);
        foreach ($records as $record) {
            $answer[] = [
                'name' => $question[0]['name'],
                'class' => $question[0]['class'],
                'ttl' => $record['ttl'],
                'data' => [
                    'type' => $question[0]['type'],
                    'value' => $record['answer'],
                ],
            ];
        }

        return $answer;
    }

    private function get_records_recursivly($domain, $type)
    {
        $result = [];
        $dns_const_name = $this->get_dns_cost_name($type);

        if (!$dns_const_name) {
            throw new Exception('Unsupported dns type to query.');
        }

        $dns_answer_name = $this->dns_answer_names[$dns_const_name];
        $records = dns_get_record($domain, constant($dns_const_name));

        foreach ($records as $record) {
            if (is_array($dns_answer_name)) {
                foreach ($dns_answer_name as $name) {
                    $answer[$name] = $record[$name];
                }
            } else {
                $answer = $record[$dns_answer_name];
            }
            $result[] = ['answer' => $answer, 'ttl' => $record['ttl']];
        }

        return $result;
    }

    private function get_dns_cost_name($type)
    {
        $const_name = "DNS_" . strtoupper($type);
        $name = defined($const_name) ? $const_name : false;

        return $name;
    }


    /**
     * Getter method for $recursion_available property
     *
     * @return boolean
     */
    public function allowsRecursion(): bool
    {
        return $this->recursion_available;
    }
      
    /**
    * Check if the resolver knows about a domain
    *
    * @param  string  $domain the domain to check for
    * @return boolean         true if the resolver holds info about $domain
    */
    public function isAuthority($domain): bool
    {
        return false;
    }
}
