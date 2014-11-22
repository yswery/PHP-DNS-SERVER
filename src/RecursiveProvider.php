<?php

namespace yswery\DNS;

use \Exception;

class RecursiveProvider extends AbstractStorageProvider {
    
    private $dns_answer_names = array(
        'DNS_A' => 'ip',
        'DNS_AAAA' => 'ipv6',
        'DNS_CNAME' => 'target',
        'DNS_TXT' => 'txt',
        'DNS_MX' => 'target',
        'DNS_NS' => 'target',
        'DNS_SOA' => array('mname', 'rname', 'serial', 'retry', 'refresh', 'expire', 'minimum-ttl'),
        'DNS_PTR' => 'target',
    );
    
    public function get_answer($question)
    {
        $answer = array();
        $domain = trim($question[0]['qname'], '.');
        $type = RecordTypeEnum::get_name($question[0]['qtype']);
        
        $records = $this->get_records_recursivly($domain, $type);
        foreach($records as $record) {
            $answer[] = array('name' => $question[0]['qname'], 'class' => $question[0]['qclass'], 'ttl' => $record['ttl'], 'data' => array('type' => $question[0]['qtype'], 'value' => $record['answer']));
        }
     
        return $answer;
    }
    
    private function get_records_recursivly($domain, $type)
    {
        $result = array();
        $dns_const_name =  $this->get_dns_cost_name($type);
        
        if (!$dns_const_name) {
           throw new Exception('Not supported dns type to query.'); 
        }
        
        $dns_answer_name = $this->dns_answer_names[$dns_const_name];
        $records = dns_get_record($domain, constant($dns_const_name));

        foreach($records as $record) {
            if(is_array($dns_answer_name)) {
                foreach($dns_answer_name as $name) {
                    $answer[$name] = $record[$name];
                }
            } else{
                $answer = $record[$dns_answer_name];
            }
            $result[] = array('answer' => $answer, 'ttl' => $record['ttl']);
        }

        return $result;
    }
    
    private function get_dns_cost_name($type)
    {
        $const_name = "DNS_".strtoupper($type);
        $name = defined($const_name) ? $const_name : false;
        
        return $name;
    }
    
}

