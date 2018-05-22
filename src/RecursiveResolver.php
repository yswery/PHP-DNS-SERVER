<?php

namespace yswery\DNS;

class RecursiveResolver implements ResolverInterface
{
    private $recursionAvailable = true;

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

    /*
     * {@inheritdoc}
     */
    public function getAnswer(array $question)
    {
        $answer = [];

        $domain = $question[0]['qname'];

        $type = RecordTypeEnum::getName($question[0]['qtype']);

        $records = $this->getRecordsRecursivly($domain, $type);
        foreach ($records as $record) {
            $answer[] = [
                'name' => $question[0]['qname'],
                'class' => $question[0]['qclass'],
                'ttl' => $record['ttl'],
                'data' => ['type' => $question[0]['qtype'], 'value' => $record['answer']],
            ];
        }

        return $answer;
    }

    private function getRecordsRecursivly($domain, $type)
    {
        $result = [];
        $dns_const_name = $this->getDnsCostName($type);

        if (!$dns_const_name) {
            throw new \Exception('Unsupported dns type to query.');
        }

        $dns_answer_name = $this->dnsAnswerNames[$dns_const_name];
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

    private function getDnsCostName($type)
    {
        $const_name = "DNS_".strtoupper($type);
        $name = defined($const_name) ? $const_name : false;

        return $name;
    }


    /*
     * {@inheritdoc}
     */
    public function allowsRecursion()
    {
        return $this->recursionAvailable;
    }

    /*
     * {@inheritdoc}
     */
    public function isAuthority($domain)
    {
        return false;
    }
}
